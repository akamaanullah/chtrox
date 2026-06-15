<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatRox - Register Account</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="auth-page">

    <div class="auth-card" style="max-width: 1000px;">
        <!-- Left Panel: Welcome Branding -->
        <div class="auth-left">
            <!-- Decorative Shapes -->
            <div class="shape shape-yellow"></div>
            <div class="shape shape-pink"></div>
            <div class="shape shape-circle-sm"></div>

            <div class="shape shape-squiggle">
                <svg width="60" height="30" viewBox="0 0 60 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 15C10 5 15 25 25 15C35 5 40 25 58 15" stroke="#94a3b8" stroke-width="3"
                        stroke-linecap="round" />
                </svg>
            </div>

            <h1 class="auth-welcome-title">Join<br>ChatRox!</h1>
            <p class="auth-welcome-text">Create your organization and start collaborating with your team.</p>

            <div class="shape shape-circle-lg"></div>
            <div class="shape shape-rect-pink"></div>

            <div class="shape shape-squiggle-bottom">
                <svg width="40" height="20" viewBox="0 0 40 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 10C8 2 12 18 20 10C28 2 32 18 38 10" stroke="#94a3b8" stroke-width="2"
                        stroke-linecap="round" />
                </svg>
            </div>

        </div>

        <!-- Right Panel: Multi-step Form -->
        <div class="auth-right">
            <h2 class="auth-form-title">Register</h2>
            <p class="auth-form-subtitle">Complete 3 simple steps to set up your organization.</p>

            <!-- Progress Indicator -->
            <div class="register-steps">
                <div class="register-step-bar active" id="bar-1"></div>
                <div class="register-step-bar" id="bar-2"></div>
                <div class="register-step-bar" id="bar-3"></div>
            </div>

            <form id="registerForm" action="index.php" method="GET">
                <!-- Step 1: Company Information -->
                <div class="step-content active" id="step-1">
                    <div class="auth-grid">
                        <div class="auth-group auth-grid-full">
                            <label class="auth-label">Company Name *</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;"
                                placeholder="Nexus Tech Inc." required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Industry *</label>
                            <select class="auth-input" style="padding-left: 20px;" required>
                                <option value="" disabled selected>Select Industry</option>
                                <option value="technology">Technology</option>
                                <option value="healthcare">Healthcare</option>
                                <option value="finance">Finance</option>
                                <option value="education">Education</option>
                                <option value="manufacturing">Manufacturing</option>
                                <option value="retail">Retail</option>
                                <option value="services">Services</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Organization Type *</label>
                            <select class="auth-input" style="padding-left: 20px;" required>
                                <option value="" disabled selected>Select Type</option>
                                <option value="corporation">Corporation</option>
                                <option value="llc">LLC</option>
                                <option value="partnership">Partnership</option>
                                <option value="sole_proprietorship">Sole Proprietorship</option>
                                <option value="non_profit">Non-Profit</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Company Email *</label>
                            <input type="email" class="auth-input" style="padding-left: 20px;"
                                placeholder="contact@nexus.com" required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Company Phone</label>
                            <input type="tel" class="auth-input" style="padding-left: 20px;"
                                placeholder="+1 234 567 890">
                        </div>
                        <div class="auth-group auth-grid-full">
                            <label class="auth-label">Company Logo *</label>
                            <div class="auth-upload-area" onclick="document.getElementById('logoInput').click()">
                                <input type="file" id="logoInput" name="company_logo" hidden required accept="image/*">
                                <div class="auth-upload-icon-box">
                                    <i data-lucide="image"></i>
                                </div>
                                <p class="auth-upload-text">Click to upload or drag & drop</p>
                                <p class="auth-upload-subtext">SVG, PNG, JPG (MAX. 800X400PX)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Location Details -->
                <div class="step-content" id="step-2">
                    <div class="auth-grid">
                        <div class="auth-group auth-grid-full">
                            <label class="auth-label">Address *</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;"
                                placeholder="123 Silicon Valley Road" required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">City *</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;"
                                placeholder="San Francisco" required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">State (Optional)</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;" placeholder="California">
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Country *</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;"
                                placeholder="United States" required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">ZIP Code (Optional)</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;" placeholder="94103">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Admin Account -->
                <div class="step-content" id="step-3">
                    <div class="auth-grid">
                        <div class="auth-group">
                            <label class="auth-label">First Name *</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;" placeholder="John"
                                required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Last Name *</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;" placeholder="Doe"
                                required>
                        </div>
                        <div class="auth-group auth-grid-full">
                            <label class="auth-label">Username *</label>
                            <input type="text" class="auth-input" style="padding-left: 20px;" placeholder="johndoe123"
                                required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Email *</label>
                            <input type="email" class="auth-input" style="padding-left: 20px;"
                                placeholder="john@nexus.com" required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Phone *</label>
                            <input type="tel" class="auth-input" style="padding-left: 20px;"
                                placeholder="+1 987 654 321" required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Password *</label>
                            <input type="password" class="auth-input" style="padding-left: 20px;" placeholder="••••••••"
                                required>
                        </div>
                        <div class="auth-group">
                            <label class="auth-label">Confirm Password *</label>
                            <input type="password" class="auth-input" style="padding-left: 20px;" placeholder="••••••••"
                                required>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="auth-nav-btns">
                    <button type="button" class="auth-submit auth-btn-back" id="btnBack"
                        style="display: none;">Back</button>
                    <button type="button" class="auth-submit" id="btnNext">
                        Next <i data-lucide="arrow-right" style="width: 18px;"></i>
                    </button>
                    <button type="submit" class="auth-submit" id="btnSubmit" style="display: none;">
                        Complete Registration <i data-lucide="check" style="width: 18px;"></i>
                    </button>
                </div>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login" class="auth-link">Sign In</a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let currentStep = 1;
        const totalSteps = 3;

        const btnNext = document.getElementById('btnNext');
        const btnBack = document.getElementById('btnBack');
        const btnSubmit = document.getElementById('btnSubmit');

        function updateSteps() {
            // Hide all steps
            document.querySelectorAll('.step-content').forEach(step => {
                step.classList.remove('active');
            });
            // Show current step
            document.getElementById(`step-${currentStep}`).classList.add('active');

            // Update Progress Bars
            for (let i = 1; i <= totalSteps; i++) {
                const bar = document.getElementById(`bar-${i}`);
                if (i < currentStep) {
                    bar.classList.add('completed');
                    bar.classList.add('active');
                } else if (i === currentStep) {
                    bar.classList.add('active');
                    bar.classList.remove('completed');
                } else {
                    bar.classList.remove('active');
                    bar.classList.remove('completed');
                }
            }

            // Update Buttons
            if (currentStep === 1) {
                btnBack.style.display = 'none';
                btnNext.style.display = 'flex';
                btnSubmit.style.display = 'none';
            } else if (currentStep === totalSteps) {
                btnBack.style.display = 'flex';
                btnNext.style.display = 'none';
                btnSubmit.style.display = 'flex';
            } else {
                btnBack.style.display = 'flex';
                btnNext.style.display = 'flex';
                btnSubmit.style.display = 'none';
            }
        }

        btnNext.addEventListener('click', () => {
            if (currentStep < totalSteps) {
                currentStep++;
                updateSteps();
            }
        });

        btnBack.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateSteps();
            }
        });

        // Logo Input Preview / Change Handler
        document.getElementById('logoInput').addEventListener('change', function (e) {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const uploadText = document.querySelector('.auth-upload-text');
                uploadText.textContent = `Selected: ${fileName}`;
                uploadText.style.color = '#65a30d';
            }
        });
    </script>
</body>

</html>