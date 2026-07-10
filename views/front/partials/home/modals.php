<!-- About Chatrox Modal -->
<div class="modal-overlay" id="aboutModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>ABOUT CHATROX</h3>
            <button class="modal-close" onclick="toggleAboutModal()">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-scroll-area">
                <p class="label-tiny">CHATROX - REAL-TIME TEAM COMMUNICATION PLATFORM</p>
                <p>ChatRox is a modern, high-performance workplace communication platform built for distributed and co-located teams. It combines real-time messaging, file collaboration, voice messages, and smart notifications into a single, cohesive workspace - so your team can stop switching between tools and start doing their best work.</p>

                <h4>Who We Are</h4>
                <p>ChatRox was built from the ground up using PHP, WebSockets, and a clean, component-driven frontend - designed to be fast, reliable, and easy to extend. Unlike generic SaaS chat tools, ChatRox is purpose-built to run within your organization's own infrastructure, giving administrators full control over data, access, and users.</p>
                <p>The platform is managed through a dedicated Admin Panel where workspace administrators can control membership, channels, announcements, and audit logs - keeping the workspace organized and secure at all times.</p>

                <h4>What ChatRox Offers</h4>
                <ul class="modal-list">
                    <li><strong>Real-Time Messaging</strong> - Instant DMs, group DMs, and channel messaging powered by a persistent WebSocket server. Messages are delivered in real time with delivery receipts and read indicators.</li>
                    <li><strong>Channels (Groups)</strong> - Create public or private channels for teams, projects, or topics. Members can be invited or join on their own. Channels support the full message experience including files, reactions, and pinning.</li>
                    <li><strong>Direct Messages &amp; Group DMs</strong> - One-on-one and multi-person private conversations with full message history, search, and media sharing.</li>
                    <li><strong>Voice Messages</strong> - Record and send voice clips directly in any conversation without leaving the chat window.</li>
                    <li><strong>File &amp; Media Sharing</strong> - Attach any file type from the message input. Images render inline in galleries; other files are displayed with size and type info and are downloadable with one click.</li>
                    <li><strong>Message Reactions</strong> - React to any message with any emoji using the emoji reaction picker. Reactions are grouped and updated in real time for everyone in the conversation.</li>
                    <li><strong>Reply Threads</strong> - Reply directly to a specific message. The reply preview appears inline with a jump link to the original.</li>
                    <li><strong>Message Forwarding</strong> - Forward any message to one or more conversations in a single action.</li>
                    <li><strong>Pinned Messages</strong> - Pin important messages so they appear in the Info Panel of the chat for easy reference.</li>
                    <li><strong>Home Dashboard</strong> - A personalized dashboard on login showing active workspace announcements, your availability status, a focus timer, team pulse (who's online by department), and a priority goals tracker.</li>
                    <li><strong>Workspace Announcements</strong> - Admins post time-bound announcements (with start and end dates) and all members receive an in-app notification and browser push notification automatically.</li>
                    <li><strong>Activity Feed</strong> - A unified notification center showing all workspace events: mentions, reactions, replies, channel invitations, join requests, and announcements. Unread items are badged in the sidebar.</li>
                    <li><strong>Files Tab</strong> - A searchable, filterable archive of every file shared across the entire workspace - organized by type and date.</li>
                    <li><strong>People Directory</strong> - Browse every member of the workspace, see their online status, and start a direct message or group chat with one click.</li>
                    <li><strong>Global Workspace Search</strong> - Search messages, files, and people across the entire workspace from the Home dashboard search bar.</li>
                    <li><strong>GIF Support</strong> - Send animated GIFs directly in any conversation using the built-in GIF picker powered by Giphy.</li>
                    <li><strong>Rich Text Formatting</strong> - Bold, italic, strikethrough, bullet lists, numbered lists, and text alignment are available in the message input toolbar.</li>
                    <li><strong>Presence &amp; Status</strong> - Set your availability (Online, Focusing, At Lunch, DND, In a Meeting) and have it reflected in real time across the workspace for all other members.</li>
                    <li><strong>Settings</strong> - Personalize your notification sounds, preferences, and profile details from the Settings tab.</li>
                </ul>

                <h4>Our Mission</h4>
                <p>We believe the best teams communicate with clarity, speed, and purpose. ChatRox exists to give every organization the tools of a world-class communication platform - without the complexity, the per-seat pricing, or the data loss risk of third-party cloud services.</p>
                <p>Every feature in ChatRox was designed with one question in mind: <em>Does this help the team do their best work?</em> If the answer is yes, it ships. If it adds friction, it doesn't.</p>
            </div>
        </div>
    </div>
</div>

<!-- Feature Guide Modal -->
<div class="modal-overlay" id="guideModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>FEATURE GUIDE</h3>
            <button class="modal-close" onclick="toggleGuideModal()">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-scroll-area">
                <p class="label-tiny">CHATROX - COMPLETE FEATURE GUIDE</p>
                <p>This guide walks you through every part of ChatRox so you can get the most out of your workspace.</p>

                <!-- PWA / IP-based Notification Alert -->
                <div style="background: rgba(79, 70, 229, 0.05); border: 1px dashed var(--indigo-300); border-radius: 8px; padding: 16px; margin: 20px 0 24px 0;">
                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                        <div style="background: var(--indigo-100); color: var(--indigo-600); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="bell-ring" size="16"></i>
                        </div>
                        <div style="font-size: 13px; line-height: 1.5; color: var(--text-primary);">
                            <strong style="color: var(--indigo-600); display: block; font-size: 14px; margin-bottom: 4px;">PWA & IP-Based Notification Setup</strong>
                            If you are accessing ChatRox via an IP address (e.g. <code>http://172.16.32.59:8887</code>) or an insecure (non-HTTPS) origin, Chrome blocks notification permissions. To bypass this and enable desktop alerts:
                            <ol style="margin: 8px 0 0 16px; padding: 0;">
                                <li>
                                    Copy and paste this URL into your Chrome address bar:
                                    <div style="display: inline-flex; align-items: center; gap: 6px; vertical-align: middle; margin-left: 4px;">
                                        <code style="background: #ffffff; padding: 2px 6px; border: 1px solid var(--border-color); border-radius: 4px; font-family: monospace; font-size: 11px;">chrome://flags/#unsafely-treat-insecure-origin-as-secure</code>
                                        <button onclick="copyChromeFlagUrl(this)" style="background: none; border: none; cursor: pointer; color: var(--indigo-600); display: inline-flex; align-items: center; justify-content: center; padding: 4px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(79,70,229,0.08)'" onmouseout="this.style.background='none'" title="Copy URL">
                                            <i data-lucide="copy" size="14"></i>
                                        </button>
                                    </div>
                                </li>
                                <li>In the input field of that section, paste your site URL (e.g. <code>http://172.16.32.59:8887</code>).</li>
                                <li>Change the dropdown status next to it to <strong>Enabled</strong>.</li>
                                <li>Click the <strong>Relaunch / Launch</strong> button at the bottom right to restart Chrome.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>1. LEFT SIDEBAR - Navigation</h4>
                    <ul class="modal-list">
                        <li><strong>Home</strong> - Your workspace dashboard. Shows active announcements, availability status, focus timer, team pulse, priority goals, and recent successes.</li>
                        <li><strong>DMs (Direct Messages)</strong> - Your private conversations. Shows unread count and last message preview per conversation. Click any row to open it. Use the search bar to filter by name. Press <strong>+</strong> to go to People and start a new DM.</li>
                        <li><strong>Channels</strong> - All workspace channels you belong to. Click a channel row to open it. Use <strong>New Channel</strong> to create one, or browse all channels via Browse Channels.</li>
                        <li><strong>People</strong> - Full directory of every workspace member. See name, avatar, and presence status. Click <strong>Chat</strong> to open or start a DM.</li>
                        <li><strong>Activity</strong> - Your unified notification feed. All workspace events appear here - reactions, replies, mentions, channel invitations, join requests, and announcements. Visiting this tab marks all notifications as read and clears the badge.</li>
                        <li><strong>More (···)</strong> - Expands to reach Files and Settings.</li>
                        <li><strong>Account (bottom)</strong> - Opens your profile side panel. Edit your name, bio/about, and profile photo. All changes save globally.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>2. HOME PAGE</h4>
                    <ul class="modal-list">
                        <li><strong>Workspace Announcements</strong> - Cards for active admin announcements. Only announcements within their active date range are shown. Click <strong>Details</strong> on any card to read the full message in a modal.</li>
                        <li><strong>Availability</strong> - Set your status (Online, Focusing, At Lunch, DND, In a Meeting). Your status is visible to all other members in real time.</li>
                        <li><strong>Focus Time</strong> - A built-in Pomodoro-style timer. Enter a duration (e.g. 25 minutes), click <strong>Start Focus</strong> to begin, and <strong>Stop Focus</strong> to reset. Helps you block off deep work sessions.</li>
                        <li><strong>Team Pulse</strong> - See who is currently online, organized by department or team. Updates in real time as presence changes.</li>
                        <li><strong>Priority Goal</strong> - Shows the workspace's current top goal and its progress percentage (set by the admin).</li>
                        <li><strong>Recent Successes</strong> - Highlights recent wins posted by the admin. Click <strong>View Archives</strong> for older successes.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>3. DMs (Direct Messages)</h4>
                    <ul class="modal-list">
                        <li><strong>Conversation list</strong> - Your active DMs, sorted by most recent. Shows unread count (badge) and last message preview.</li>
                        <li><strong>Search DMs</strong> - Type in the "Search dms…" bar to filter your list by name in real time.</li>
                        <li><strong>Start a new DM</strong> - Click <strong>+</strong> in the DMs header (takes you to People) or find someone in People and click Chat.</li>
                        <li><strong>Group DMs</strong> - You can chat with more than one person in a private group DM. Group DMs show all member avatars and support the same features as one-on-one DMs.</li>
                        <li><strong>Open a conversation</strong> - Click any row to open it in the main area.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>4. CHANNELS</h4>
                    <ul class="modal-list">
                        <li><strong>Channel list</strong> - All channels you are a member of, with unread count badges.</li>
                        <li><strong>New Channel</strong> - Opens a form: enter a channel name (required), description (optional), and privacy (public or private). Public channels are visible to all workspace members; private channels require an invitation.</li>
                        <li><strong>Browse Channels</strong> - Discover and join any public channel in the workspace. Private channels are hidden unless you are already a member.</li>
                        <li><strong>Join Request</strong> - For private channels, you can send a join request. The channel admin will approve or reject it, and you receive a notification either way.</li>
                        <li><strong>Open a channel</strong> - Click any channel row to open it.</li>
                        <li><strong>Members</strong> - See who is in a channel from the Info Panel (i icon). Admins can manage membership.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>5. CHAT WINDOW (DM or Channel)</h4>
                    <ul class="modal-list">
                        <li><strong>Header</strong> - Shows the chat name and online/presence status. Contains <strong>Search (🔍)</strong> to search messages in the current chat, and the <strong>(···)</strong> button to open the Info Panel (Media, Files, Pinned messages).</li>
                        <li><strong>Messages</strong> - Your own messages appear on the right (highlighted in the workspace accent color). Others' messages appear on the left with their avatar and name. Date dividers separate messages by day.</li>
                        <li><strong>Delivery indicators</strong> - A single checkmark means sent; a double checkmark means delivered; a colored double checkmark means read.</li>
                        <li><strong>Input area</strong> - Type your message in the text box at the bottom. Press <strong>Enter</strong> to send (or click the send button). You can also use:
                            <ul style="margin-top:6px; margin-left:16px;">
                                <li>😊 <strong>Emoji picker</strong> - Insert any emoji into your message.</li>
                                <li>📎 <strong>Attach files</strong> - Upload any file (images, docs, etc). Images display inline; other files show with a download link. You can also drag and drop files directly into the chat.</li>
                                <li>🎤 <strong>Voice message</strong> - Click the mic icon, record your voice note, then send it. Plays back inline in the conversation.</li>
                                <li>🎞 <strong>GIFs</strong> - Click the GIF button to search and send animated GIFs from Giphy.</li>
                                <li><strong>Rich text toolbar</strong> - Bold, italic, strikethrough, bullet list, numbered list, and text alignment buttons are available above the input box.</li>
                            </ul>
                        </li>
                    </ul>
                    <p style="margin-top:10px; font-weight:700; color:#1e293b;">Message Actions (hover over any message to see the action bar):</p>
                    <ul class="modal-list">
                        <li><strong>😊 React</strong> - Opens the emoji picker. Click an emoji to add your reaction. Click your own reaction to remove it.</li>
                        <li><strong>↩ Reply</strong> - Creates an inline reply referencing that message. A preview appears in the chat with a jump link.</li>
                        <li><strong>📌 Pin</strong> - Pins the message to the chat. Pinned messages appear under the Pinned tab in the Info Panel.</li>
                        <li><strong>→ Forward</strong> - Opens the forward modal. Select one or more DMs or channels and click <strong>Forward</strong>. A copy is sent to each selected destination.</li>
                        <li><strong>🗑 Delete</strong> - Permanently removes the message from the conversation for everyone.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>6. INFO PANEL (Click ⓘ in the chat header)</h4>
                    <p>The Info Panel slides open from the right side of the chat and has four tabs:</p>
                    <ul class="modal-list">
                        <li><strong>Profile</strong> - Shows the other person's (or channel's) name, bio, and profile photo.</li>
                        <li><strong>Media</strong> - Grid of all images and media shared in this conversation. Click to view full-size.</li>
                        <li><strong>Files</strong> - List of all non-image files shared. Click the filename to download.</li>
                        <li><strong>Pinned</strong> - All messages pinned in this chat. Click <strong>Jump</strong> to scroll directly to that message in the thread.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>7. ACTIVITY FEED</h4>
                    <p>Your Activity feed is a unified notification center. Every workspace event that affects you appears here:</p>
                    <ul class="modal-list">
                        <li>Someone reacted to one of your messages</li>
                        <li>Someone replied to your message</li>
                        <li>You were mentioned with @</li>
                        <li>An admin posted a new announcement</li>
                        <li>A channel join request was approved or rejected</li>
                        <li>You were invited to a private channel</li>
                    </ul>
                    <p>Each card shows who performed the action, what they did, and when. Click <strong>View Original Message</strong> on any card to jump directly to the relevant message in its conversation. Opening the Activity tab automatically marks all notifications as read and clears the sidebar badge.</p>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>8. FILES TAB</h4>
                    <p>The Files tab is a workspace-wide file archive. Every file ever shared in any DM or channel you are a member of is listed here. You can:</p>
                    <ul class="modal-list">
                        <li><strong>Search</strong> - Filter files by name using the search bar.</li>
                        <li><strong>Filter by type</strong> - Switch between All, Images, Documents, and Other.</li>
                        <li><strong>Download</strong> - Click any file to download it directly.</li>
                        <li><strong>See context</strong> - Each entry shows the filename, who uploaded it, and in which conversation.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>9. PROFILE &amp; SETTINGS</h4>
                    <ul class="modal-list">
                        <li><strong>Profile Panel</strong> - Click your avatar in the bottom-left of the sidebar. Edit your first/last name, bio/about, and upload a profile photo. Click <strong>Save</strong> to apply changes globally across the workspace.</li>
                        <li><strong>Settings Tab</strong> - Access from More (···). Configure notification sound tones, workspace preferences, and other personal options.</li>
                        <li><strong>Presence Status</strong> - Set via the Home page or profile panel. Options: Online, Focusing, At Lunch, DND (Do Not Disturb), In a Meeting.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>10. QUICK TIPS</h4>
                    <ul class="modal-list">
                        <li>The unread badge on the Activity icon updates in real time when a new notification arrives - no refresh needed.</li>
                        <li>You can drag and drop any file directly into the chat window to upload it without clicking the attachment button.</li>
                        <li>Only one emoji picker and one message action bar are visible at a time - clicking elsewhere dismisses them.</li>
                        <li>Visiting the <strong>Activity</strong> tab automatically clears all unread notification badges.</li>
                        <li>The footer links (About, Privacy, Guide) all open in clean modals so you never lose your place.</li>
                        <li>Admins can manage the workspace from the separate <strong>Admin Panel</strong> - including users, channels, announcements, and audit logs.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal-overlay" id="privacyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>PRIVACY POLICY</h3>
            <button class="modal-close" onclick="togglePrivacyModal()">
                <i data-lucide="x" size="20"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-scroll-area">
                <p class="label-tiny">LAST UPDATED: JULY 2026</p>
                <p>ChatRox ("we", "our", or "us") is committed to protecting the privacy of every member who uses this platform. This Privacy Policy explains what information we collect, how it is used, how it is protected, and what rights you have over your data.</p>

                <h4>1. What Information We Collect</h4>
                <ul class="modal-list">
                    <li><strong>Account information:</strong> Your name, email address, profile photo, bio, and any other profile details you provide when creating or editing your account.</li>
                    <li><strong>Messages and content:</strong> Text messages, voice messages, file attachments, emoji reactions, and any other content you send within conversations (DMs or channels).</li>
                    <li><strong>Presence and activity data:</strong> Your online/availability status, when you read messages (read receipts), and activity events generated by your actions (e.g. reactions, replies).</li>
                    <li><strong>Files and media:</strong> Any files or images you upload through the platform are stored and associated with your account.</li>
                    <li><strong>Usage data:</strong> Information about how you interact with ChatRox - features you use, navigation patterns - to help us improve the product.</li>
                    <li><strong>Device and log data:</strong> Your device type, browser, IP address, and access timestamps, collected for security monitoring and troubleshooting.</li>
                </ul>

                <h4>2. How We Use Your Information</h4>
                <ul class="modal-list">
                    <li>To deliver and operate the ChatRox platform - including sending and receiving messages, file sharing, notifications, and real-time features.</li>
                    <li>To display your profile, name, and avatar to other members within your workspace.</li>
                    <li>To generate activity feed events and notifications relevant to your interactions.</li>
                    <li>To maintain security and detect abuse, unauthorized access, or policy violations.</li>
                    <li>To improve and develop platform features based on aggregate, anonymized usage patterns.</li>
                    <li>To comply with applicable legal obligations.</li>
                </ul>
                <p>We do not sell, rent, or trade your personal data to third parties for marketing or advertising purposes.</p>

                <h4>3. Who Can See Your Data</h4>
                <ul class="modal-list">
                    <li><strong>Workspace members:</strong> Your name, avatar, bio, and presence status are visible to all members within the same workspace.</li>
                    <li><strong>Conversation participants:</strong> Messages in a DM are visible only to the participants of that conversation. Messages in a channel are visible only to channel members.</li>
                    <li><strong>Workspace administrators:</strong> Admins can see audit logs of workspace actions, manage users, channels, and announcements. They do not have access to private DM message content through the Admin Panel.</li>
                    <li><strong>Internal team:</strong> Only authorized personnel may access user data for legitimate technical or security purposes, under strict access controls.</li>
                </ul>

                <h4>4. Data Storage and Security</h4>
                <p>ChatRox runs on your organization's own infrastructure or a designated server environment. Your data does not leave that environment to third-party cloud providers unless explicitly configured by your administrator.</p>
                <p>We apply industry-standard security measures including:</p>
                <ul class="modal-list">
                    <li>Encrypted sessions (HTTPS) for all data in transit.</li>
                    <li>Secure HTTP-only, same-site cookies for authentication.</li>
                    <li>CSRF token protection on all sensitive operations.</li>
                    <li>Strict access controls - each user can only read data they are permitted to access.</li>
                    <li>WebSocket connections authenticated with short-lived single-use tickets.</li>
                </ul>

                <h4>5. Data Retention</h4>
                <p>Your account data and message history are retained for as long as your account is active within the workspace. When a message is deleted by a user, it is removed from view for all participants. When an account is deactivated or removed by an administrator, your data is handled in accordance with your organization's data policies and applicable law.</p>

                <h4>6. Your Rights</h4>
                <p>Depending on your location and applicable laws, you may have the right to:</p>
                <ul class="modal-list">
                    <li><strong>Access</strong> - Request a copy of your personal data held by ChatRox.</li>
                    <li><strong>Correction</strong> - Update your name, photo, or bio at any time through your Profile panel.</li>
                    <li><strong>Deletion</strong> - Request removal of your account and associated data by contacting your workspace administrator.</li>
                    <li><strong>Restriction</strong> - Request that your data not be used in certain ways.</li>
                    <li><strong>Portability</strong> - Request an export of your data in a structured format.</li>
                </ul>

                <h4>7. Cookies</h4>
                <p>ChatRox uses a single, essential session cookie to authenticate your login. This cookie is strictly necessary for the platform to function and is not used for tracking or advertising. It is set as HTTP-only, same-site, and (where HTTPS is active) secure.</p>

                <h4>8. Changes to This Policy</h4>
                <p>We may update this Privacy Policy from time to time. When we do, we will update the "Last Updated" date at the top. Continued use of ChatRox after changes are posted constitutes your acceptance of the revised policy. For significant changes, your workspace administrator may post an announcement.</p>

                <h4>9. Contact</h4>
                <p>For privacy questions, data requests, or concerns, please contact your workspace administrator. If your administrator needs to escalate, they can reach our support team through the workspace support channel.</p>
            </div>
        </div>
    </div>
</div>

<!-- Announcement Details Modal -->
<div class="modal-overlay modal-overlay--security" id="announcementModal">
    <div class="modal-content modal-content--security">
        <div class="modal-header-security">
            <div class="security-header-left">
                <div class="security-icon-wrap">
                    <span class="security-icon-flash" id="announcementModalIcon">📢</span>
                </div>
                <h2 id="announcementModalTitle">Announcement</h2>
            </div>
            <span class="tag important" id="announcementModalTag">UPDATE</span>
        </div>

        <div class="modal-body-security">
            <div class="modal-scroll-area-security custom-scrollbar">
                <div class="security-details-text" id="announcementModalBody"></div>
                <div class="posted-by-wrap">
                    <div class="posted-avatar" id="announcementModalAvatar">A</div>
                    <div class="posted-info">
                        <span class="posted-label">POSTED BY</span>
                        <span class="posted-name" id="announcementModalAuthor">Workspace Admin</span>
                    </div>
                    <span class="posted-date" id="announcementModalDate"></span>
                </div>
            </div>

            <div class="modal-footer-security">
                <button type="button" class="security-close-btn" onclick="closeAnnouncementModal()">CLOSE</button>
            </div>
        </div>
    </div>
</div>

<!-- Workspace Security Upgrade Modal -->
<div class="modal-overlay modal-overlay--security" id="securityModal">
    <div class="modal-content modal-content--security">
        <div class="modal-header-security">
            <div class="security-header-left">
                <div class="security-icon-wrap">
                    <span class="security-icon-flash">🚨</span>
                </div>
                <h2>Workspace Security Upgrade</h2>
            </div>
            <span class="tag important">IMPORTANT</span>
        </div>

        <div class="modal-body-security">
            <div class="modal-scroll-area-security custom-scrollbar">

                <div class="security-details-text">
                    <p>Mandatory 2FA migration scheduled for all employees this Friday at 6:00
                        PM. Please ensure your authenticator apps are ready.</p>
                </div>

                <div class="security-details-text">
                    <p>As part of our ongoing commitment to workspace security, we are rolling out two-factor
                        authentication (2FA) across all ChatRox accounts. This applies to every team member-no
                        exceptions. Before the migration window, please install an authenticator app (Google
                        Authenticator, Microsoft Authenticator, or Authy) on your phone. On Friday at 6:00 PM you
                        will receive a prompt to link your account; the process takes about two minutes. If you have
                        any issues or use a work phone that cannot install apps, contact IT Support before Thursday
                        EOD so we can arrange an alternative. Thank you for helping keep our workspace secure.</p>
                </div>

                <div class="posted-by-wrap">
                    <div class="posted-avatar">I</div>
                    <div class="posted-info">
                        <span class="posted-label">POSTED BY</span>
                        <span class="posted-name">IT Support</span>
                    </div>
                    <span class="posted-date">Mar 4, 2026</span>
                </div>
            </div>

            <div class="modal-footer-security">
                <button type="button" class="security-close-btn" onclick="toggleSecurityModal()">CLOSE</button>
            </div>
        </div>
    </div>
</div>
