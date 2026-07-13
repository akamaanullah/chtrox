<?php

namespace App\Services;

use App\Core\Model;
use PDO;

class AiService
{
    /**
     * Generates an AI response using the Gemini API.
     *
     * @param int $workspaceId
     * @param int $conversationId
     * @param int $userMemberId
     * @param string $userMessageText
     * @param int $aiMemberId The workspace member ID of the AI.
     * @return string Safe, clean HTML string.
     */
    public static function generateResponse(int $workspaceId, int $conversationId, int $userMemberId, string $userMessageText, int $aiMemberId): string
    {
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        if (empty($apiKey)) {
            return "<p>Hello! I am <strong>ChatRox AI</strong>.</p><p>To activate my intelligence, please configure the <code>GEMINI_API_KEY</code> in your <code>.env</code> file.</p>";
        }

        // Fetch last 15 messages to form conversation context
        $db = Model::db();
        $stmt = $db->prepare("
            SELECT m.sender_id, m.body, m.message_type
            FROM messages m
            WHERE m.conversation_id = ? AND m.deleted_for_everyone_at IS NULL
            ORDER BY m.id DESC
            LIMIT 15
        ");
        $stmt->execute([$conversationId]);
        $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

        // Format history into Gemini's contents payload structure
        $contents = [];
        foreach ($rows as $row) {
            $text = trim(html_entity_decode(strip_tags($row['body']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (empty($text)) {
                continue;
            }

            $isAi = ((int)$row['sender_id'] === $aiMemberId);
            $role = $isAi ? 'model' : 'user';

            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $text]
                ]
            ];
        }

        // If history is empty (e.g. initial message), add the current one as fallback
        if (empty($contents)) {
            $plainText = trim(html_entity_decode(strip_tags($userMessageText), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $plainText]
                ]
            ];
        }

        // Prepare the payload for Gemini API
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
        $payload = json_encode([
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'You are ChatRox AI, a helpful, professional, and friendly virtual workspace assistant integrated inside the ChatRox enterprise communication platform. Keep your replies structured, concise, and professional. Use markdown formatting where appropriate (e.g. bold, bullet points, headers).']
                ]
            ]
        ]);

        // Call API via cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing environments compatibility

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "<p>I apologize, but I encountered an error connecting to my server: " . htmlspecialchars($error) . "</p>";
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($response, true);
            $errMsg = $decoded['error']['message'] ?? 'Unknown API error';
            return "<p>I apologize, but my intelligence engine returned an error (HTTP {$httpCode}): " . htmlspecialchars($errMsg) . "</p>";
        }

        $result = json_decode($response, true);
        $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty(trim($aiText))) {
            return "<p>I received an empty response. Please try rephrasing your question.</p>";
        }

        // Parse markdown formatting into clean HTML
        return self::markdownToHtml($aiText);
    }

    /**
     * Converts basic markdown to allowed HTML format.
     */
    public static function markdownToHtml(string $markdown): string
    {
        // 1. Code blocks ( ```javascript ... ``` )
        $markdown = preg_replace_callback('/```(\w*)\n([\s\S]*?)```/', function($matches) {
            $lang = !empty($matches[1]) ? 'language-' . htmlspecialchars($matches[1]) : 'language-javascript';
            // We escape HTML inside the code blocks
            return '<pre><code class="' . $lang . '">' . htmlspecialchars($matches[2]) . '</code></pre>';
        }, $markdown);

        // 2. Inline code ( `code` )
        $markdown = preg_replace_callback('/`([^`]+)`/', function($matches) {
            return '<code>' . htmlspecialchars($matches[1]) . '</code>';
        }, $markdown);

        // 3. Headers (### header)
        $markdown = preg_replace('/^(?:#{1,6})\s+(.+)$/m', '<p><strong>$1</strong></p>', $markdown);

        // 4. Bold ( **bold** )
        $markdown = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $markdown);

        // 5. Italic ( *italic* )
        $markdown = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $markdown);

        // 6. Lists (unordered list items)
        $lines = explode("\n", $markdown);
        $inList = false;
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*[-*+]\s+(.+)$/', $line, $matches)) {
                $item = $matches[1];
                if (!$inList) {
                    $lines[$i] = '<ul><li>' . $item . '</li>';
                    $inList = true;
                } else {
                    $lines[$i] = '<li>' . $item . '</li>';
                }
            } else {
                if ($inList) {
                    $lines[$i] = '</ul>' . $line;
                    $inList = false;
                }
            }
        }
        if ($inList) {
            $lines[] = '</ul>';
        }
        $markdown = implode("\n", $lines);

        // 7. Convert double newlines to paragraphs or single newlines to <br> (ignoring inside pre/code blocks)
        $parts = preg_split('/(<pre[\s\S]*?<\/pre>)/', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if (strpos($part, '<pre') !== 0) {
                $parts[$i] = nl2br($part);
            }
        }
        
        return implode('', $parts);
    }
}
