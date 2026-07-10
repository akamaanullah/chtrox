<div class="modal-overlay" id="feedbackModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 9999; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
    <div class="modal-content" style="background: #ffffff; border-radius: 20px; width: 100%; max-width: 550px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid rgba(226, 232, 240, 0.8); overflow: hidden; transform: scale(0.95); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <!-- Header -->
        <div style="padding: 24px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(147, 51, 234, 0.05) 100%);">
            <div>
                <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="message-square-plus" style="color: var(--indigo-600); width: 22px; height: 22px;"></i>
                    Feedback & Report
                </h3>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Help us make ChatRox better for your team.</p>
            </div>
            <button type="button" onclick="closeFeedbackModal()" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 6px; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#0f172a';" onmouseout="this.style.background='none'; this.style.color='#94a3b8';">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Form -->
        <form id="feedbackForm" style="padding: 32px;" enctype="multipart/form-data">
            <div id="feedbackAlert" style="display: none; padding: 12px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;"></div>

            <!-- Rating Area (Premium Mood Picker) -->
            <div style="margin-bottom: 24px; text-align: center;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 12px;">How is your experience with ChatRox?</label>
                <div style="display: inline-flex; gap: 16px; justify-content: center;">
                    <button type="button" class="feedback-rate-btn" data-rating="1" title="Terrible" style="background: none; border: none; font-size: 28px; cursor: pointer; opacity: 0.4; transition: transform 0.2s, opacity 0.2s; outline: none;">😠</button>
                    <button type="button" class="feedback-rate-btn" data-rating="2" title="Bad" style="background: none; border: none; font-size: 28px; cursor: pointer; opacity: 0.4; transition: transform 0.2s, opacity 0.2s; outline: none;">🙁</button>
                    <button type="button" class="feedback-rate-btn" data-rating="3" title="Okay" style="background: none; border: none; font-size: 28px; cursor: pointer; opacity: 0.4; transition: transform 0.2s, opacity 0.2s; outline: none;">😐</button>
                    <button type="button" class="feedback-rate-btn" data-rating="4" title="Good" style="background: none; border: none; font-size: 28px; cursor: pointer; opacity: 0.4; transition: transform 0.2s, opacity 0.2s; outline: none;">🙂</button>
                    <button type="button" class="feedback-rate-btn" data-rating="5" title="Amazing!" style="background: none; border: none; font-size: 28px; cursor: pointer; opacity: 0.4; transition: transform 0.2s, opacity 0.2s; outline: none;">😍</button>
                </div>
                <input type="hidden" name="rating" id="feedbackRatingValue" value="">
            </div>

            <!-- Feedback Type -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Feedback Type *</label>
                <select name="type" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; background: #ffffff; outline: none; transition: border-color 0.2s; box-sizing: border-box;" required onfocus="this.style.borderColor='var(--indigo-600)';" onblur="this.style.borderColor='#cbd5e1';">
                    <option value="bug">Bug / Error Report</option>
                    <option value="feature">Feature Suggestion</option>
                    <option value="usability">Usability / Design Issue</option>
                    <option value="feedback" selected>General Feedback</option>
                </select>
            </div>

            <!-- Subject -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Subject *</label>
                <input type="text" name="subject" placeholder="Summarize your report or feedback" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; transition: border-color 0.2s; box-sizing: border-box;" required onfocus="this.style.borderColor='var(--indigo-600)';" onblur="this.style.borderColor='#cbd5e1';">
            </div>

            <!-- Message (Comment Box) -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Details / Message *</label>
                <textarea name="message" placeholder="Provide full details of your report, suggestions, or comments here..." rows="4" style="width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; color: #1e293b; outline: none; transition: border-color 0.2s; font-family: inherit; resize: vertical; box-sizing: border-box;" required onfocus="this.style.borderColor='var(--indigo-600)';" onblur="this.style.borderColor='#cbd5e1';"></textarea>
            </div>

            <!-- Attachment File -->
            <div style="margin-bottom: 28px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Attach Screenshot or Log (Optional)</label>
                <div style="position: relative; display: flex; align-items: center; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; background: #f8fafc; transition: border-color 0.2s;" onmouseover="this.style.borderColor='var(--indigo-500)';" onmouseout="this.style.borderColor='#cbd5e1';">
                    <i data-lucide="paperclip" style="color: #64748b; width: 18px; height: 18px; margin-right: 8px;"></i>
                    <span id="feedbackFileName" style="font-size: 13px; color: #64748b;">Choose file... (Max 5MB)</span>
                    <input type="file" name="attachment" id="feedbackAttachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid #f1f5f9; padding-top: 24px;">
                <button type="button" onclick="closeFeedbackModal()" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='#ffffff';">Cancel</button>
                <button type="submit" id="feedbackSubmitBtn" style="padding: 10px 24px; border-radius: 8px; border: none; background: var(--indigo-600); color: #ffffff; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s;" onmouseover="this.style.background='var(--indigo-700)';" onmouseout="this.style.background='var(--indigo-600)';">
                    <span>Submit Report</span>
                    <i data-lucide="send" style="width: 14px; height: 14px;"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        // Modal toggles
        window.openFeedbackModal = function () {
            var modal = document.getElementById('feedbackModal');
            if (!modal) return;
            modal.classList.add('active');
            modal.style.display = 'flex';
            setTimeout(function () {
                modal.style.opacity = '1';
                modal.querySelector('.modal-content').style.transform = 'scale(1)';
            }, 10);
            if (window.lucide) {
                window.lucide.createIcons({
                    attrs: {
                        'stroke-width': 2
                    }
                });
            }
        };

        window.closeFeedbackModal = function () {
            var modal = document.getElementById('feedbackModal');
            if (!modal) return;
            modal.classList.remove('active');
            modal.style.opacity = '0';
            modal.querySelector('.modal-content').style.transform = 'scale(0.95)';
            setTimeout(function () {
                modal.style.display = 'none';
                resetFeedbackForm();
            }, 300);
        };

        function resetFeedbackForm() {
            var form = document.getElementById('feedbackForm');
            if (!form) return;
            form.reset();
            
            // Reset rating buttons
            var rateBtns = form.querySelectorAll('.feedback-rate-btn');
            rateBtns.forEach(function (btn) {
                btn.style.opacity = '0.4';
                btn.style.transform = 'scale(1)';
            });
            document.getElementById('feedbackRatingValue').value = '';
            
            // Reset file label
            document.getElementById('feedbackFileName').textContent = 'Choose file... (Max 5MB)';
            
            // Hide alert
            var alertEl = document.getElementById('feedbackAlert');
            alertEl.style.display = 'none';
            alertEl.className = '';
            alertEl.textContent = '';
            
            // Reset button state
            var submitBtn = document.getElementById('feedbackSubmitBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.querySelector('span').textContent = 'Submit Report';
            }
        }

        // Initialize events when document is ready
        function initFeedbackEvents() {
            var modal = document.getElementById('feedbackModal');
            if (!modal) return;

            // Rating buttons hover & click
            var rateBtns = modal.querySelectorAll('.feedback-rate-btn');
            rateBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var rating = btn.getAttribute('data-rating');
                    document.getElementById('feedbackRatingValue').value = rating;
                    
                    rateBtns.forEach(function (b) {
                        if (b.getAttribute('data-rating') === rating) {
                            b.style.opacity = '1';
                            b.style.transform = 'scale(1.2)';
                        } else {
                            b.style.opacity = '0.3';
                            b.style.transform = 'scale(0.9)';
                        }
                    });
                });
            });

            // Attachment input name preview
            var attachmentInput = document.getElementById('feedbackAttachment');
            if (attachmentInput) {
                attachmentInput.addEventListener('change', function () {
                    var fileName = 'Choose file... (Max 5MB)';
                    if (attachmentInput.files && attachmentInput.files.length > 0) {
                        fileName = attachmentInput.files[0].name;
                    }
                    document.getElementById('feedbackFileName').textContent = fileName;
                });
            }

            // Form Submit
            var form = document.getElementById('feedbackForm');
            if (form) {
                // Remove existing listeners first to prevent duplicates
                var newForm = form.cloneNode(true);
                form.parentNode.replaceChild(newForm, form);
                
                // Re-bind attachment input change since cloned
                var newAttachmentInput = document.getElementById('feedbackAttachment');
                if (newAttachmentInput) {
                    newAttachmentInput.addEventListener('change', function () {
                        var fileName = 'Choose file... (Max 5MB)';
                        if (newAttachmentInput.files && newAttachmentInput.files.length > 0) {
                            fileName = newAttachmentInput.files[0].name;
                        }
                        document.getElementById('feedbackFileName').textContent = fileName;
                    });
                }
                
                // Re-bind rating buttons since cloned
                var newRateBtns = newForm.querySelectorAll('.feedback-rate-btn');
                newRateBtns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var rating = btn.getAttribute('data-rating');
                        document.getElementById('feedbackRatingValue').value = rating;
                        
                        newRateBtns.forEach(function (b) {
                            if (b.getAttribute('data-rating') === rating) {
                                b.style.opacity = '1';
                                b.style.transform = 'scale(1.2)';
                            } else {
                                b.style.opacity = '0.3';
                                b.style.transform = 'scale(0.9)';
                            }
                        });
                    });
                });

                newForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var alertEl = document.getElementById('feedbackAlert');
                    alertEl.style.display = 'none';

                    var submitBtn = document.getElementById('feedbackSubmitBtn');
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.7';
                    submitBtn.querySelector('span').textContent = 'Submitting...';

                    var formData = new FormData(newForm);

                    // Add CSRF Token to POST payload
                    var csrfToken = (window.CHATROX && window.CHATROX.csrfToken) ? window.CHATROX.csrfToken : '';
                    formData.set('_csrf_token', csrfToken);

                    fetch((window.CHATROX ? window.CHATROX.baseUrl : '') + '/api/v1/feedback', {
                        method: 'POST',
                        body: formData
                    })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (!response.ok) {
                                throw new Error(data.error || 'Failed to submit feedback');
                            }
                            return data;
                        });
                    })
                    .then(function (data) {
                        alertEl.style.display = 'block';
                        alertEl.className = 'alert alert-success';
                        alertEl.style.background = '#dcfce7';
                        alertEl.style.color = '#166534';
                        alertEl.style.border = '1px solid #bbf7d0';
                        alertEl.textContent = data.message || 'Feedback submitted successfully!';
                        
                        setTimeout(function () {
                            closeFeedbackModal();
                        }, 2000);
                    })
                    .catch(function (err) {
                        alertEl.style.display = 'block';
                        alertEl.className = 'alert alert-danger';
                        alertEl.style.background = '#fee2e2';
                        alertEl.style.color = '#991b1b';
                        alertEl.style.border = '1px solid #fecaca';
                        alertEl.textContent = err.message || 'Failed to submit feedback. Please try again.';
                        
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.querySelector('span').textContent = 'Submit Report';
                    });
                });
            }
        }

        // Run setup on load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFeedbackEvents);
        } else {
            initFeedbackEvents();
        }

        // Support chatrox page loads (turbolinks-like router transitions)
        document.addEventListener('chatrox:page_load', initFeedbackEvents);
    })();
</script>
