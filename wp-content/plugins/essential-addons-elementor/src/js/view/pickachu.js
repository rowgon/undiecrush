function EAELRenderPickachu($scope, $) {
    const $characterWrapper = $scope.find('.eael-animated-character-wrapper');
    if (!$characterWrapper.length) {
        return;
    }

    const eyeTrackingEnabled = $characterWrapper.data('eye-tracking') === 'yes';
    const passwordCoveringEnabled = $characterWrapper.data('password-covering') === 'yes';
    const excitedModeEnabled = $characterWrapper.data('excited-mode') === 'yes';
    const animationSpeedRaw = parseFloat($characterWrapper.data('animation-speed'));
    const shakeIntensityRaw = parseFloat($characterWrapper.data('shake-intensity'));
    const animationSpeed = Number.isFinite(animationSpeedRaw) ? Math.max(0.1, Math.min(3, animationSpeedRaw)) : 1;
    const shakeIntensity = Number.isFinite(shakeIntensityRaw) ? Math.max(0.1, Math.min(3, shakeIntensityRaw)) : 1;

    const getMotionDuration = function (baseDuration) {
        return baseDuration / animationSpeed;
    };

    // --- GSAP Setup ---
    gsap.defaults({ duration: 0.6, ease: "power2.out" });

    // --- Selectors (Matching index.php exactly) ---
    const innerFaceFeatures = $scope.find('.eael-pickachu-leftEye, .eael-pickachu-righEye, .eael-pickachu-nose, .eael-pickachu-mouth');
    const faceContour = $scope.find('.eael-pickachu-face, .eael-pickachu-head');
    const eyes = $scope.find('.eael-pickachu-leftEye, .eael-pickachu-righEye');
    const eyeCorneas = $scope.find('.eael-pickachu-leftEye path[fill="#F4EEE9"], .eael-pickachu-righEye path[fill="#F4EEE9"]');
    const mouthGroup = $scope.find('.eael-pickachu-mouth');
    const mouthOpen = $scope.find('.eael-pickachu-mouth .eael-pickachu-mouthOpen');
    const mouthClose = $scope.find('.eael-pickachu-mouth .eael-pickachu-mouthClose');
    const tongue = $scope.find('.eael-pickachu-mouth .eael-pickachu-toung');
    const tail = $scope.find('.eael-pickachu-tail');


    // Ears
    const ears = $scope.find('.eael-pickachu-earsLeft, .eael-pickachu-earsRight');
    const earNormalL = $scope.find('.eael-pickachu-earsLeft .eael-pickachu-earNormal');
    const earExcitedL = $scope.find('.eael-pickachu-earsLeft .eael-pickachu-earExcited');
    const earNormalR = $scope.find('.eael-pickachu-earsRight .eael-pickachu-earnormal'); // Note lowercase 'normal' in index.php
    const earExcitedR = $scope.find('.eael-pickachu-earsRight .eael-pickachu-earExcited');

    // Hands
    const handNormal = $scope.find('.eael-pickachu-handnormal');
    const leftHandGroup = $scope.find('.eael-pickachu-lefthand');
    const rightHandGroup = $scope.find('.eael-pickachu-righthand');
    const leftHandDownGroup = $scope.find('.eael-pickachu-lefthanddown');
    // Direct children paths of lefthand are the "Up" hand. 
    // We can target them by excluding the group.
    const leftHandUpParts = $scope.find('.eael-pickachu-lefthand > path');

    // --- Initialization ---
    // Hide excited ears and special hand states initially
    gsap.set([earExcitedL, earExcitedR], { autoAlpha: 0, display: "none" });
    gsap.set([earNormalL, earNormalR], { autoAlpha: 1, display: "block" });

    gsap.set([leftHandGroup, rightHandGroup], { autoAlpha: 0, display: "none" });
    gsap.set(handNormal, { autoAlpha: 1, display: "block" });

    // For the left hand structure (Up vs Down inside same group)
    // Initially ensure 'Up' parts are ready to show if group is shown
    gsap.set(leftHandUpParts, { autoAlpha: 1, display: "block" });
    gsap.set(leftHandDownGroup, { autoAlpha: 0, display: "none" });

    // --- State Variables ---
    let excitedMode = false;
    let wiggleTween = null;
    let expressionTimer = null;
    let tongueTween = null;
    let tailTween = null;

    gsap.set([earNormalL, earNormalR, earExcitedL, earExcitedR], { transformOrigin: "50% 90%" });
    gsap.set([eyes, mouthGroup], { transformOrigin: "center center" });
    gsap.set(tail, { transformOrigin: "8% 88%" });
    gsap.set(mouthOpen, { autoAlpha: 0, display: "none" });
    gsap.set(tongue, { autoAlpha: 1, display: "block" });
    gsap.set(mouthClose, { autoAlpha: 1, display: "block" });

    function setCorneaOffset(x, y, duration) {
        gsap.to(eyeCorneas, {
            x: x,
            y: y,
            duration: getMotionDuration(duration),
            ease: "power2.out"
        });
    }

    function blinkEyes() {
        gsap.timeline()
            .to(eyes, {
                scaleY: 0.08,
                transformOrigin: "center center",
                duration: 0.08,
                ease: "power1.in"
            })
            .to(eyes, {
                scaleY: 1,
                duration: 0.12,
                ease: "power1.out"
            });
    }

    function setMouthClosed(isClosed) {
        if (isClosed) {
            if (tongueTween) {
                tongueTween.kill();
                tongueTween = null;
            }
            gsap.to(tongue, { y: 0, rotation: 0, autoAlpha: 0, display: "none", duration: 0.12, ease: "power1.out" });
            gsap.to(mouthOpen, { autoAlpha: 0, display: "none", duration: 0.12 });
            gsap.to(mouthClose, { autoAlpha: 1, display: "block", duration: 0.12 });
            return;
        }

        gsap.to(mouthClose, { autoAlpha: 0, display: "none", duration: 0.12 });
        gsap.to(mouthOpen, { autoAlpha: 1, display: "block", duration: 0.12 });
        gsap.to(tongue, { autoAlpha: 1, display: "block", duration: 0.12 });

        if (tongueTween) {
            tongueTween.kill();
        }
        tongueTween = gsap.fromTo(tongue,
            { y: -0.8, rotation: -1.5, transformOrigin: "center top" },
            {
                y: 1.2,
                rotation: 1.5,
                transformOrigin: "center top",
                duration: 0.22,
                yoyo: true,
                repeat: -1,
                ease: "sine.inOut"
            }
        );
    }

    function scheduleIdleExpression() {
        const delay = 2.5 + Math.random() * 3.5;
        expressionTimer = gsap.delayedCall(delay, function () {
            blinkEyes();

            if (Math.random() > 0.55) {
                setMouthClosed(true);
                gsap.delayedCall(0.2, function () {
                    setMouthClosed(false);
                });
            }

            scheduleIdleExpression();
        });
    }

    function isValidEmailFormat(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value || "");
    }

    function updateExcitedState() {
        const emailValue = $emailInput.val();
        const emailFocused = document.activeElement && document.activeElement.id === "eael-user-login";
        window.toggleEars(emailFocused && isValidEmailFormat(emailValue));
    }

    function animateEarsAttached(contourX, rotate, duration) {
        // Extra side-specific offset keeps the outer ear base attached during turns.
        const leftAttachBoost = contourX > 0 ? contourX * 0.55 : 0;
        const rightAttachBoost = contourX < 0 ? contourX * 0.55 : 0;
        const baseLift = 3;

        gsap.to($scope.find(".eael-pickachu-earsLeft"), {
            x: contourX + leftAttachBoost,
            y: baseLift,
            rotation: rotate * 0.08,
            transformOrigin: "50% 90%",
            duration: getMotionDuration(duration)
        });

        gsap.to($scope.find(".eael-pickachu-earsRight"), {
            x: contourX + rightAttachBoost,
            y: baseLift,
            rotation: rotate * 0.08,
            transformOrigin: "50% 90%",
            duration: getMotionDuration(duration)
        });
    }

    // --- Functions ---

    /**
     * 1. Face Turn Animation
     * Simulates 3D turning by moving inner features more than the outer contour.
     */
    window.setFacePosition = function (direction) {
        let featureX = 0, featureY = 0, contourX = 0, contourY = 0, skew = 0;

        if (direction === 'left') {
            featureX = -15;
            contourX = -5;
            featureY = -1;
            skew = -3;
        } else if (direction === 'right') {
            featureX = 15;
            contourX = 5;
            featureY = -1;
            skew = 3;
        } else if (direction === 'center') {
            featureX = 0;
            contourX = 0;
            featureY = 0;
            contourY = 0;
            skew = 0;
        }

        // Animate Inner Features (Eyes, Nose, Mouth)
        gsap.to(innerFaceFeatures, {
            x: featureX,
            y: featureY,
            skewX: skew * 0.5,
            rotation: 0,
            duration: getMotionDuration(0.8)
        });

        // Animate Face Contour (Background)
        gsap.to(faceContour, {
            x: contourX,
            y: contourY,
            skewX: skew,
            rotation: 0,
            transformOrigin: "center 80%", // Standardize origin
            duration: getMotionDuration(0.8),
            onComplete: function () {
                if (direction === 'center') {
                    // Prevent drift by clearing inline transforms
                    gsap.set([innerFaceFeatures, faceContour, ears, eyeCorneas], { clearProps: "transform,transformOrigin" });
                }
            }
        });

        // Animate Ears
        animateEarsAttached(contourX, skew, 0.8);

        setCorneaOffset(featureX * 0.14, featureY * 0.08, 0.8);
    };

    /**
     * 2. Ear Excited Mode (Wiggle)
     */
    window.toggleEars = function (isExcited) {
        excitedMode = excitedModeEnabled && isExcited;

        if (excitedMode) {
            const earShakeX = 0.75 * shakeIntensity;
            const earShakeY = -0.75 * shakeIntensity;
            const tailRotate = 3 * shakeIntensity;
            const tailShiftX = 1.2 * shakeIntensity;
            const tailShiftY = -0.2 * shakeIntensity;

            // Crossfade to excited ears
            gsap.to([earNormalL, earNormalR], { autoAlpha: 0, display: "none", duration: 0.2 });
            gsap.to([earExcitedL, earExcitedR], { autoAlpha: 1, display: "block", duration: 0.2 });

            // Start Wiggle
            if (wiggleTween) wiggleTween.kill();
            wiggleTween = gsap.timeline({
                repeat: -1,
                yoyo: true,
                defaults: { duration: 0.16, ease: "sine.inOut", transformOrigin: "50% 90%" }
            });
            wiggleTween.fromTo(earExcitedL, { x: 0, y: 0, rotation: 0 }, { x: earShakeX, y: earShakeY, rotation: 0 }, 0);
            wiggleTween.fromTo(earExcitedR, { x: 0, y: 0, rotation: 0 }, { x: -earShakeX, y: earShakeY, rotation: 0 }, 0);

            if (tailTween) tailTween.kill();
            tailTween = gsap.fromTo(tail,
                { rotation: -tailRotate, x: 0, y: 0 },
                {
                    rotation: tailRotate,
                    x: tailShiftX,
                    y: tailShiftY,
                    transformOrigin: "8% 88%",
                    yoyo: true,
                    repeat: -1,
                    duration: 0.18,
                    ease: "sine.inOut"
                }
            );

        } else {
            // Stop Wiggle and reset
            if (wiggleTween) wiggleTween.kill();
            gsap.to([earExcitedL, earExcitedR], { x: 0, y: 0, rotation: 0, duration: 0.2 });
            if (tailTween) tailTween.kill();
            gsap.to(tail, {
                rotation: 0,
                x: 0,
                y: 0,
                transformOrigin: "8% 88%",
                duration: 0.22
            });

            // Crossfade back to normal
            gsap.to([earExcitedL, earExcitedR], { autoAlpha: 0, display: "none", duration: 0.2 });
            gsap.to([earNormalL, earNormalR], { autoAlpha: 1, display: "block", duration: 0.2 });
        }
    };

    /**
     * 3. Hand States
     * Smooth transitions between hand positions.
     */
    window.setHandState = function (state) {
        if (state === 'normal') {
            // Show body hands, hide ext hands
            gsap.to([leftHandGroup, rightHandGroup], { autoAlpha: 0, display: "none", duration: getMotionDuration(0.25) });
            gsap.to(handNormal, { autoAlpha: 1, display: "block", duration: getMotionDuration(0.25) });
        }
        else if (state === 'cover') {
            // Both hands up covering eyes
            // Hide normal
            gsap.to(handNormal, { autoAlpha: 0, display: "none", duration: getMotionDuration(0.25) });

            // Show Left/Right groups
            gsap.to([leftHandGroup, rightHandGroup], { autoAlpha: 1, display: "block", duration: getMotionDuration(0.25) });

            // Inside Left: Ensure 'Up' is visible, 'Down' is hidden
            gsap.to(leftHandDownGroup, { autoAlpha: 0, display: "none", duration: getMotionDuration(0.25) });
            gsap.to(leftHandUpParts, { autoAlpha: 1, display: "block", duration: getMotionDuration(0.25) });
        }
        else if (state === 'peek') {
            // One hand down (Left hand down variant), Right hand still up
            // Ensure main groups visible
            gsap.to(handNormal, { autoAlpha: 0, display: "none", duration: getMotionDuration(0.25) });
            gsap.to([leftHandGroup, rightHandGroup], { autoAlpha: 1, display: "block", duration: getMotionDuration(0.25) });

            // Left Hand specifically: Hide 'Up' parts, Show 'Down' group
            gsap.to(leftHandUpParts, { autoAlpha: 0, display: "none", duration: getMotionDuration(0.25) });
            gsap.to(leftHandDownGroup, { autoAlpha: 1, display: "block", duration: getMotionDuration(0.25) });
        }
    }

    /**
     * 4. Face Tracking (Input Progress)
     * Moves face from Down-Left to Down-Right based on progress (0 to 1).
     */
    window.trackFace = function (progress) {
        // Clamp progress 0 to 1
        progress = Math.max(0, Math.min(1, progress));

        // Interpolate values
        // Start (0): Down-Left
        // End (1): Down-Right

        // featureX: -10 to 10
        const featureX = -10 + (20 * progress);

        // contourX: -4 to 4
        const contourX = -4 + (8 * progress);

        // skew: 5 to -5
        const skew = 5 - (10 * progress);

        // rotate: -5 to 5
        const rotate = -5 + (10 * progress);

        // Keep vertical movement tight so neck area does not show.
        const featureY = 6;
        const contourY = 0;

        // Apply
        gsap.to(innerFaceFeatures, {
            x: featureX,
            y: featureY,
            skewX: skew * 0.5,
            rotation: rotate,
            transformOrigin: "center 80%",
            duration: getMotionDuration(0.3) // Faster duration for typing response
        });

        gsap.to(faceContour, {
            x: contourX,
            y: contourY,
            skewX: skew,
            rotation: rotate * 0.5,
            transformOrigin: "center 80%",
            duration: getMotionDuration(0.3)
        });

        // Animate Ears
        // Keeping them "attached" means mimicking the face movement but with less amplitude
        animateEarsAttached(contourX * 0.95, rotate, 0.3);

        setCorneaOffset(featureX * 0.16, featureY * 0.05, 0.3);
    }

    // --- Input Event Listeners ---
    const $emailInput = $scope.find('#eael-user-login');
    const $passwordInput = $scope.find('#eael-user-password');
    const $showPasswordToggle = $scope.find('#wp-hide-pw');

    // Fix for Email Selection Support
    // "email" type inputs often don't support selectionStart in modern browsers.
    // We switch to type="text" but keep inputmode="email" for mobile keyboards.
    $emailInput.attr('type', 'text');
    $emailInput.attr('inputmode', 'email');

    function handleInputTrack(e) {
        const target = e.target;
        const val = $(target).val();
        const max = $(target).width() / (parseFloat($(target).css('font-size')) * 0.3);

        // Password field should NOT track face
        if (target.id === 'eael-user-password') return;

        let currentPos = val.length; // Default to end

        // Try to get cursor position (selectionStart)
        try {
            if (typeof target.selectionStart === 'number') {
                currentPos = target.selectionStart;
            }
        } catch (err) {
            // Fallback to val.length if selectionStart is not supported
            currentPos = val.length;
        }

        const progress = currentPos / max;

        if (eyeTrackingEnabled) {
            window.trackFace(progress);
        } else {
            window.setFacePosition('center');
        }
    }

    // Helper Text Logic 
    function handleFocus(e) {
        $(e.target).parent().addClass('focusWithText');
        handleInputTrack(e); // Trigger tracking immediately on focus
    }

    function handleBlur(e) {
        if ($(e.target).val() === '') {
            $(e.target).parent().removeClass('focusWithText');
        }

        // RESET FACE ON BLUR
        window.setFacePosition('center');

        // RESET HANDS ON BLUR (Optional, but good for "normal position" request)
        // If we were covering eyes (password) or peeking, go back to normal.
        // Unless logic dictates otherwise. User said "character should be in normal position".
        // Assuming "normal position" implies hands down.
        window.setHandState('normal');
    }

    // specific events to catch cursor movement (click, keyup) and typing (input)
    $emailInput.on('focus input click keyup', handleInputTrack);
    $emailInput.on('focus', function (e) {
        handleFocus(e);
        window.setHandState('normal');
        setMouthClosed(false);
        updateExcitedState();
    });
    $emailInput.on('input keyup click', updateExcitedState);
    $emailInput.on('blur', function (e) {
        handleBlur(e);
        updateExcitedState();
    });

    // Password: No tracking, just Cover/Peek logic
    $passwordInput.on('focus', function (e) {
        $(e.target).parent().addClass('focusWithText');
        window.setFacePosition('center');
        blinkEyes();
        setMouthClosed(true);
        window.toggleEars(false);
        if (passwordCoveringEnabled) {
            // Check input type to determine current visibility state
            if ($passwordInput.attr('type') === 'text') {
                window.setHandState('peek');
            } else {
                window.setHandState('cover');
            }
        } else {
            window.setHandState('normal');
        }
    });

    $passwordInput.on('blur', function (e) {
        // Delay blur to see if we clicked the eye icon toggle
        setTimeout(() => {
            const active = document.activeElement;
            const clickedToggle = active && (active.id === 'wp-hide-pw' || $(active).closest('#wp-hide-pw').length);
            if (!clickedToggle && active.id !== 'eael-user-password') {
                setMouthClosed(false);
                handleBlur(e);
            }
        }, 100);
    });

    // Toggle Handler - Eye icon click controls hand state reaction
    // Deferred to run after login-register.js toggles the input type
    $showPasswordToggle.on('click', function () {
        if (!passwordCoveringEnabled) {
            return;
        }

        setTimeout(function () {
            if ($passwordInput.attr('type') === 'text') {
                window.setHandState('peek');
            } else {
                window.setHandState('cover');
            }
        }, 0);
    });

    scheduleIdleExpression();
}

jQuery(window).on("elementor/frontend/init", function () {

    if (eael.elementStatusCheck('EAELRenderPickachu')) {
        return false;
    }

    elementorFrontend.hooks.addAction("frontend/element_ready/eael-login-register.default", EAELRenderPickachu);
});
