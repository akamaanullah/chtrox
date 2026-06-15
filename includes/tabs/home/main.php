<div class="content-inner">
    <!-- Top Header -->
    <div class="dash-top">
        <div class="greeting">
            <div class="office-tag">OFFICE HQ DASHBOARD</div>
            <h1>Good Morning, <span class="text-primary">James</span> 👋</h1>
            <p>Thursday, February 26</p>
        </div>
        <div class="pulse-widget">
            <div class="pulse-stat">
                <div class="label">Workspace Pulse</div>
                <div class="val">94% Active</div>
            </div>
            <div class="avatar-group">
                <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&q=80&w=150"
                    alt="Jon">
                <img src="https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&q=80&w=150"
                    alt="Sarah">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150"
                    alt="Mia">
                <img src="https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&q=80&w=150"
                    alt="Ben">
                <div class="avatar-more">+10</div>
            </div>
        </div>
    </div>

    <!-- Stats and Quick Connect Grid -->
    <div class="dash-grid">
        <div class="stat-card green">
            <span class="stat-label">Pending</span>
            <span class="stat-value">4</span>
            <span class="stat-footer">Unread Pings</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Projects</span>
            <span class="stat-value">12</span>
            <span class="stat-footer text-primary">2 active</span>
        </div>
        <div class="stat-card dark">
            <span class="stat-label">Focus Time</span>
            <span class="stat-value" id="focusTimerValue">0:00</span>
            <div class="card-actions">
                <button type="button" class="action-btn primary" id="focusTimerStart">START</button>
                <button type="button" class="action-btn" id="focusTimerReset">RESET</button>
            </div>
        </div>
        <div class="quick-connect-card">
            <span class="stat-label">Quick Connect</span>
            <a href="people" class="qc-item"
                style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center;">
                <div class="qc-content">
                    <div class="qc-icon-box"><i data-lucide="message-square" size="14"></i></div>
                    <span>NEW DM</span>
                </div>
                <i data-lucide="chevron-right" size="14" class="chevron"></i>
            </a>
            <a href="browse-channels" class="qc-item"
                style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; align-items: center;">
                <div class="qc-content">
                    <div class="qc-icon-box"><i data-lucide="hash" size="14"></i></div>
                    <span>JOIN CHANNEL</span>
                </div>
                <i data-lucide="chevron-right" size="14" class="chevron"></i>
            </a>
        </div>
    </div>

    <!-- Middle Grid: AI Brief and Recent Success -->
    <div class="bottom-grid">
        <div class="global-search-card">
            <div class="search-card-header">
                <div class="search-icon-box"><i data-lucide="search" size="20"></i></div>
                <h3>Global Workspace Search</h3>
            </div>
            <div class="search-input-wrap">
                <input type="text" placeholder="Search messages, files, or people across Chatrox..."
                    class="dash-search-input">
                <div class="search-submit">
                    <i data-lucide="arrow-right" size="18"></i>
                </div>
            </div>
            <div class="quick-tags">
                <span class="q-tag">#development</span>
                <span class="q-tag">#marketing</span>
                <span class="q-tag">@oliver</span>
                <span class="q-tag">reports.pdf</span>
            </div>
        </div>


        <div class="world-clocks-section">
            <div class="section-header">
                <h3>World Clocks</h3>
                <span class="label-tiny">Global HQ Time</span>
            </div>
            <?php
            ob_start();
            ?>
            <div class="clock-markers" aria-hidden="true">
                <?php for ($marker = 1; $marker <= 12; $marker++): ?>
                    <span class="clock-marker" style="--marker-i: <?php echo $marker; ?>"><?php echo $marker; ?></span>
                <?php endfor; ?>
            </div>
            <?php $clockMarkersHtml = ob_get_clean(); ?>
            <div class="clocks-grid">
                <!-- Pakistan Clock -->
                <div class="clock-card" data-timezone="PK">
                    <div class="clock-face">
                        <?php echo $clockMarkersHtml; ?>
                        <div class="clock-hand hour" id="pk-hour"></div>
                        <div class="clock-hand minute" id="pk-min"></div>
                        <div class="clock-hand second" id="pk-sec"></div>
                        <div class="clock-center"></div>
                    </div>
                    <div class="clock-info">
                        <h4>Pakistan</h4>
                        <span class="digital-time" id="pk-digital">--:-- --</span>
                    </div>
                </div>

                <!-- Houston Clock -->
                <div class="clock-card" data-timezone="HOU">
                    <div class="clock-face">
                        <?php echo $clockMarkersHtml; ?>
                        <div class="clock-hand hour" id="hou-hour"></div>
                        <div class="clock-hand minute" id="hou-min"></div>
                        <div class="clock-hand second" id="hou-sec"></div>
                        <div class="clock-center"></div>
                    </div>
                    <div class="clock-info">
                        <h4>Houston</h4>
                        <span class="digital-time" id="hou-digital">--:-- --</span>
                    </div>
                </div>

                <!-- New York Clock -->
                <div class="clock-card" data-timezone="NY">
                    <div class="clock-face">
                        <?php echo $clockMarkersHtml; ?>
                        <div class="clock-hand hour" id="ny-hour"></div>
                        <div class="clock-hand minute" id="ny-min"></div>
                        <div class="clock-hand second" id="ny-sec"></div>
                        <div class="clock-center"></div>
                    </div>
                    <div class="clock-info">
                        <h4>New York</h4>
                        <span class="digital-time" id="ny-digital">--:-- --</span>
                    </div>
                </div>

                <!-- Phoenix Clock -->
                <div class="clock-card" data-timezone="PHX">
                    <div class="clock-face">
                        <?php echo $clockMarkersHtml; ?>
                        <div class="clock-hand hour" id="phx-hour"></div>
                        <div class="clock-hand minute" id="phx-min"></div>
                        <div class="clock-hand second" id="phx-sec"></div>
                        <div class="clock-center"></div>
                    </div>
                    <div class="clock-info">
                        <h4>Phoenix</h4>
                        <span class="digital-time" id="phx-digital">--:-- --</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- Announcements Section -->
    <div class="announcements">
        <div class="ann-header">
            <h2>Workspace Announcements</h2>
        </div>
        <div class="ann-grid">

            <!-- Announcement 1-->
            <div class="ann-card">
                <div class="ann-card-top">
                    <span class="ann-card-icon" aria-hidden="true">🚨</span>
                    <span class="tag important">IMPORTANT</span>
                </div>
                <h3>Workspace Security Upgrade</h3>
                <p>Mandatory 2FA migration scheduled for all employees this Friday at 6:00 PM. Please ensure your
                    authenticator apps are ready.</p>
                <div class="ann-footer">
                    <span class="ann-date">25/02/2026</span>
                    <a href="javascript:void(0)" class="details-btn" onclick="toggleSecurityModal()">Details</a>
                </div>
            </div>

            <!-- Announcement 2-->
            <div class="ann-card">
                <div class="ann-card-top">
                    <span class="ann-card-icon" aria-hidden="true">🎂</span>
                    <span class="tag celebration">CELEBRATION</span>
                </div>
                <h3>Team Celebration</h3>
                <p>It's Charlotte's birthday! Join us in the pantry at 4 PM for some cake and coffee. Let's make it
                    special!</p>
                <div class="ann-footer">
                    <span class="ann-date">25/02/2026</span>
                    <a href="javascript:void(0)" class="details-btn" onclick="toggleSecurityModal()">Details</a>
                </div>
            </div>

            <!-- Announcement 3-->
            <div class="ann-card">
                <div class="ann-card-top">
                    <span class="ann-card-icon" aria-hidden="true">📢</span>
                    <span class="tag update">UPDATE</span>
                </div>
                <h3>Office Renovation</h3>
                <p>The 4th-floor lounge is being renovated. It will be closed for the next 2 weeks. Please use the
                    3rd-floor huddle space instead.</p>
                <div class="ann-footer">
                    <span class="ann-date">25/02/2026</span>
                    <a href="javascript:void(0)" class="details-btn" onclick="toggleSecurityModal()">Details</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Final Footer -->
    <footer class="final-footer">
        <div class="footer-brand">
            <h4>CHATROX</h4>
            <p>BUILT FOR HIGH-PERFORMANCE DISTRIBUTED TEAMS</p>
        </div>
        <div class="footer-links">
            <a href="javascript:void(0)" onclick="toggleAboutModal()">About</a>
            <a href="javascript:void(0)" onclick="togglePrivacyModal()">Privacy</a>
            <a href="javascript:void(0)" onclick="toggleGuideModal()">Guide</a>
        </div>
    </footer>

</div>

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
                <p>Chatrox is a modern, real-time communication platform designed to bring teams together. Our mission
                    is to provide seamless collaboration tools that enhance productivity and foster meaningful
                    connections in the digital workplace.</p>

                <h4>Who We Are</h4>
                <p>Chatrox is a cutting-edge team communication platform built with modern web technologies. We
                    specialize in creating intuitive, real-time messaging solutions that help organizations stay
                    connected and productive.</p>

                <h4>What We Do</h4>
                <p>We provide comprehensive communication tools including private messaging (DMs), group channels, file
                    sharing, voice messages, and real-time notifications. Our platform supports both individual and team
                    collaboration needs.</p>

                <h4>Our Features</h4>
                <p>Our platform offers advanced features like message reactions, file attachments, voice recording,
                    search functionality, user profiles, announcements, and a clean interface to enhance your
                    communication experience.</p>

                <h4>Our Mission</h4>
                <p>At Chatrox, we believe in the power of seamless communication to drive productivity and
                    collaboration. Our platform is designed to bring teams together, regardless of their location or
                    time zone. We're committed to providing intuitive, reliable, and feature-rich communication tools
                    that adapt to your team's unique needs and workflow.</p>

                <p>Our vision is to create a communication ecosystem where ideas flow freely, information is easily
                    accessible, and collaboration happens naturally. We continuously innovate to stay ahead of
                    communication trends while maintaining the simplicity and reliability that our users depend on.</p>

                <p>Every feature we develop is crafted with the user experience in mind, ensuring that technology serves
                    people, not the other way around. From real-time messaging to file sharing, from voice messages to
                    smart notifications — everything is designed to make your work life more connected and efficient.
                </p>
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
                <p class="label-tiny">CHATROX – FEATURE GUIDE</p>
                <p>This guide explains every feature and how to use it.</p>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>1. NAVIGATION (Left sidebar)</h4>
                    <ul class="modal-list">
                        <li><strong>Home</strong> – Dashboard with announcements, stats, focus timer, and team pulse.
                            Click any item to open it.</li>
                        <li><strong>Direct (DMs)</strong> – Your private message list. Search in "Search dms...", click
                            a conversation to open it, or use the + to start a new DM (takes you to People).</li>
                        <li><strong>People</strong> – Directory of everyone in the workspace. Click "Chat" on a person
                            to start or open a DM with them.</li>
                        <li><strong>Groups (Channels)</strong> – List of channels. Click "New Channel" to create one
                            (name, description, optional); click "Jump In" on a channel to open it.</li>
                        <li><strong>Activity</strong> – Feed of workspace updates (reactions, replies, etc.). Click an
                            item to see details or go to the original message.</li>
                        <li><strong>Account</strong> – Opens your profile panel. Edit name, about/bio, and profile
                            photo; changes save when you click Save.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>2. HOME PAGE</h4>
                    <ul class="modal-list">
                        <li><strong>Workspace Announcements</strong> – Cards for important updates, celebrations,
                            events. Click "DETAILS" to open the full announcement in a modal.</li>
                        <li><strong>Availability</strong> – Set status: Online, Focusing, Lunch, DND, Meeting (shown to
                            others).</li>
                        <li><strong>Focus Time</strong> – Timer (e.g. 25 min). Use "Start Focus" to run it, "Stop Focus"
                            to stop.</li>
                        <li><strong>Team Pulse</strong> – Shows who’s online by team (e.g. Creative, Engineering).</li>
                        <li><strong>Priority Goal</strong> – Current goal and progress (e.g. System Audit, 65%).</li>
                        <li><strong>Recent Success</strong> – Recent wins; "View Archives" for more.</li>
                        <li><strong>Footer</strong> – Documentation, Support, About (company info), Privacy (policy),
                            and this Guide.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>3. DMs (Direct Messages)</h4>
                    <ul class="modal-list">
                        <li><strong>Left:</strong> List of your DM conversations. Unread count and last message preview
                            shown.</li>
                        <li><strong>Search</strong> – Type in "Search dms..." to filter conversations.</li>
                        <li><strong>Click a row</strong> – Opens that chat in the main area.</li>
                        <li><strong>Start new</strong> – Use + or go to People and click "Chat" on someone.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>4. CHANNELS (Groups)</h4>
                    <ul class="modal-list">
                        <li><strong>Left:</strong> List of channels. "New Channel" opens a modal: enter name and
                            optional description, then create.</li>
                        <li><strong>Click "Jump In" on a channel</strong> – Opens that channel chat.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>5. CHAT WINDOW (When a DM or channel is open)</h4>
                    <ul class="modal-list">
                        <li><strong>Header</strong> – Chat name, online status, Search (Q), "AI Brief" button, and (i)
                            for the info panel.</li>
                        <li><strong>Messages</strong> – Your messages on the right (purple), others on the left. Hover a
                            message to see the action bar.</li>
                    </ul>
                    <p style="margin-top: 10px; font-weight: 700; color: #1e293b;">Message actions (hover over a
                        message):</p>
                    <ul class="modal-list">
                        <li><strong>Smiley (😊)</strong> – Open emoji reaction picker. Click an emoji to add a reaction.
                        </li>
                        <li><strong>Reply</strong> – Reply to that message (thread/reply flow).</li>
                        <li><strong>Pin</strong> – Pin the message; pinned messages appear in the chat info panel.</li>
                        <li><strong>Forward</strong> – Open forward modal: select one or more chats and click "Forward".
                        </li>
                        <li><strong>Delete</strong> – Delete the message.</li>
                        <li><strong>Reactions</strong> – Show under the message; add/remove using the smiley.</li>
                        <li><strong>Reply preview</strong> – Replied messages show a short preview with jump
                            functionality.</li>
                        <li><strong>Files/Images</strong> – Attachments and image grids are clickable.</li>
                        <li><strong>Input area</strong> – Type in the box. Use emoji picker or paperclip to attach
                            files. Send with Enter or button. Use @mention for people.</li>
                        <li><strong>AI Brief</strong> – In the header: get a quick summary for the current chat.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>6. CHAT INFO PANEL (Click (i) in chat header)</h4>
                    <p>Tabs: Profile | Media | Files | Pinned</p>
                    <ul class="modal-list">
                        <li><strong>Profile</strong> – Other person’s (or channel) info and bio.</li>
                        <li><strong>Media</strong> – Shared images/media in this chat.</li>
                        <li><strong>Files</strong> – Shared files; click to open/download.</li>
                        <li><strong>Pinned</strong> – Pinned messages; click "Jump" to go to the thread location.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>7. FORWARDING MESSAGES</h4>
                    <ul class="modal-list">
                        <li>Hover a message → click Forward (arrow icon).</li>
                        <li>Modal opens with a list of your chats. Check one or more.</li>
                        <li>Click "Forward" – A copy is sent to each selected chat.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>8. PROFILE (Account panel)</h4>
                    <ul class="modal-list">
                        <li>Open from the left rail: Account.</li>
                        <li>Edit your name, bio, and profile picture. Click Save to apply globally.</li>
                    </ul>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>9. ACTIVITY FEED</h4>
                    <p>Lists recent workspace activity. Click "View Original Message" to jump to the location in its
                        chat.</p>
                </div>

                <div class="modal-section" style="margin-bottom: 30px;">
                    <h4>10. QUICK TIPS</h4>
                    <ul class="modal-list">
                        <li>Only one reaction picker and one action bar are visible at a time.</li>
                        <li>Use Search in the chat header for specific message filtering.</li>
                        <li>Footer links (About, Privacy, Guide) all open in modals for quick reference.</li>
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
                <p class="label-tiny">LAST UPDATED: FEBRUARY 2026</p>
                <p>Chatrox ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains
                    how we collect, use, store, and protect your information when you use our workplace communication
                    platform.</p>

                <h4>Information We Collect</h4>
                <ul class="modal-list">
                    <li><strong>Account information:</strong> name, email, profile photo, and status you provide when
                        using the platform.</li>
                    <li><strong>Messages and content:</strong> text messages, file attachments, voice messages, and
                        reactions you send within the workspace.</li>
                    <li><strong>Usage data:</strong> how you use the product (e.g. features used, activity) to improve
                        our services and security.</li>
                    <li><strong>Device and log data:</strong> device type, browser, IP address, and access times for
                        security and troubleshooting.</li>
                </ul>

                <h4>How We Use Your Information</h4>
                <p>We use your information to provide, maintain, and improve Chatrox; to deliver messages and
                    notifications; to enforce our terms and policies; to protect security; and to comply with legal
                    obligations. We do not sell your personal data.</p>

                <h4>Data Retention</h4>
                <p>We retain your account and message data for as long as your account is active or as needed to provide
                    the service. When you or your organization deletes data or closes an account, we delete or anonymize
                    it in line with our retention policy and applicable law.</p>

                <h4>Security</h4>
                <p>We use industry-standard measures (encryption, access controls, secure infrastructure) to protect
                    your data. Access to personal data is limited to authorized personnel and only for legitimate
                    purposes.</p>

                <h4>Sharing and Disclosure</h4>
                <p>We may share data with service providers who assist in operating our platform, under strict
                    agreements. We may disclose data when required by law, to protect rights and safety, or with your or
                    your organization's consent.</p>

                <h4>Your Rights</h4>
                <p>Depending on your location, you may have the right to access, correct, delete, or export your data,
                    and to object to or restrict certain processing. Contact your workspace administrator or us (see
                    below) to exercise these rights.</p>

                <h4>Contact</h4>
                <p>For privacy questions or requests, contact your organization's administrator or reach out to us via
                    the Support option in this workspace.</p>
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
                        authentication (2FA) across all Chatrox accounts. This applies to every team member—no
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