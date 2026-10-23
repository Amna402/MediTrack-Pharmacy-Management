/* MediTrack: input validation for Sign Up, Sign In and Contact Us.
   Frontend only (HTML + CSS + JavaScript). The Node.js backend will be connected later. */
(function () {
    'use strict';

    // ---------- 1. rules: each returns '' when valid, otherwise the error message ----------
    var rules = {
        full_name: function (v) {
            if (!v) return 'Full name is required.';
            if (v.length < 3) return 'Full name must be at least 3 characters.';
            if (v.length > 50) return 'Full name must be 50 characters or fewer.';
            if (!/^[A-Za-z][A-Za-z .'-]*$/.test(v)) return "Full name can contain only letters, spaces, . ' and -.";
            return '';
        },
        name: function (v) {
            if (!v) return 'Name is required.';
            if (v.length < 3) return 'Name must be at least 3 characters.';
            if (v.length > 50) return 'Name must be 50 characters or fewer.';
            if (!/^[A-Za-z][A-Za-z .'-]*$/.test(v)) return "Name can contain only letters, spaces, . ' and -.";
            return '';
        },
        email: function (v) {
            if (!v) return 'Email is required.';
            if (v.length > 100) return 'Email must be 100 characters or fewer.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'Enter a valid email address, for example name@example.com.';
            return '';
        },
        phone: function (v) {
            if (!v) return 'Phone number is required.';
            if (!/^03[0-9]{9}$/.test(v)) return 'Enter an 11-digit mobile number starting with 03, for example 03001234567.';
            return '';
        },
        role: function (v) {
            return v ? '' : 'Please select your role.';
        },
        subject: function (v) {
            if (!v) return 'Subject is required.';
            if (v.length < 3) return 'Subject must be at least 3 characters.';
            if (v.length > 100) return 'Subject must be 100 characters or fewer.';
            return '';
        },
        message: function (v) {
            if (!v) return 'Message is required.';
            if (v.length < 10) return 'Message must be at least 10 characters.';
            if (v.length > 500) return 'Message must be 500 characters or fewer.';
            return '';
        }
    };

    function passwordChecks(v) {
        return {
            length: v.length >= 8,
            upper: /[A-Z]/.test(v),
            lower: /[a-z]/.test(v),
            digit: /[0-9]/.test(v),
            special: /[^A-Za-z0-9]/.test(v)
        };
    }

    // checks one field and returns its error message ('' means valid)
    function validateField(form, el) {
        var type = form.getAttribute('data-form'); // register, login or contact
        var name = el.name;

        if (name === 'password') {
            var pw = el.value; // passwords are never trimmed
            if (!pw) return 'Password is required.';
            if (pw.length > 64) return 'Password must be 64 characters or fewer.';
            if (type === 'register') {
                var c = passwordChecks(pw);
                if (!c.length) return 'Password must be at least 8 characters.';
                if (!c.upper) return 'Password must contain an uppercase letter.';
                if (!c.lower) return 'Password must contain a lowercase letter.';
                if (!c.digit) return 'Password must contain a digit.';
                if (!c.special) return 'Password must contain a special character, for example @ # $ !';
            }
            return '';
        }
        if (name === 'confirm_password') {
            if (!el.value) return 'Please confirm your password.';
            if (el.value !== form.elements.password.value) return 'Passwords do not match.';
            return '';
        }
        if (name === 'terms') {
            return el.checked ? '' : 'You must accept the terms to continue.';
        }
        if (rules[name]) return rules[name](el.value.trim());
        return '';
    }

    // ---------- 2. showing messages ----------
    function showError(form, el, message) {
        var box = form.querySelector('[data-error-for="' + el.name + '"]');
        if (box) box.textContent = message;
        if (el.type !== 'checkbox') el.classList.toggle('invalid', !!message);
        el.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    function check(form, el) {
        var message = validateField(form, el);
        showError(form, el, message);
        return !message;
    }

    function showOk(text) {
        var el = document.querySelector('[data-alert-ok]');
        if (el) { el.textContent = text; el.hidden = false; }
    }

    // ---------- 3. connect every form ----------
    Array.prototype.forEach.call(document.querySelectorAll('form[data-form]'), function (form) {
        var type = form.getAttribute('data-form');
        var fields = Array.prototype.filter.call(form.elements, function (el) {
            return el.name && el.type !== 'submit' && el.type !== 'button';
        });
        var touched = {};

        fields.forEach(function (el) {
            // check when the user leaves a field, then keep checking while typing
            el.addEventListener('blur', function () { touched[el.name] = true; check(form, el); });
            el.addEventListener('input', function () {
                if (el.name === 'phone') el.value = el.value.replace(/[^0-9]/g, '').slice(0, 11); // digits only
                if (touched[el.name]) check(form, el);
                if (el.name === 'password' && touched.confirm_password) check(form, form.elements.confirm_password);
            });
            el.addEventListener('change', function () { touched[el.name] = true; check(form, el); });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var firstBad = null;
            fields.forEach(function (el) {
                touched[el.name] = true;
                if (!check(form, el) && !firstBad) firstBad = el;
            });
            if (firstBad) { firstBad.focus(); return; } // stop: there is an error

            // all fields are valid: send the data to the PHP file, which saves it in MySQL
            var data = {};
            fields.forEach(function (el) {
                if (el.name === 'confirm_password' || el.name === 'terms') return;
                data[el.name] = el.type === 'password' ? el.value : el.value.trim();
            });

            function showFail(text) {
                var box = document.querySelector('[data-alert]');
                if (box) { box.textContent = text; box.hidden = false; }
            }
            var failBox = document.querySelector('[data-alert]');
            if (failBox) failBox.hidden = true;

            fetch(type + '.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(function (res) {
                return res.json().then(function (body) { return { ok: res.ok, body: body }; });
            })
            .then(function (r) {
                if (!r.ok) { showFail(r.body.message || 'Something went wrong.'); return; }
                if (type === 'register') {
                    window.location.href = 'login.html?registered=1';
                } else if (type === 'login') {
                    sessionStorage.setItem('meditrack_name', r.body.name);
                    sessionStorage.setItem('meditrack_role', r.body.role);
                    window.location.href = 'dashboard.html';
                } else if (type === 'contact') {
                    form.reset();
                    fields.forEach(function (el) { showError(form, el, ''); touched[el.name] = false; });
                    var count = document.getElementById('msg-count');
                    if (count) count.textContent = '0';
                    showOk('Thank you. Your message has been recorded.');
                }
            })
            .catch(function () {
                showFail('Cannot reach the server. Start Apache and MySQL in XAMPP and open the site from http://localhost/meditrack/');
            });
        });
    });

    // ---------- 4. small extras ----------
    // password checklist on the Sign Up page
    var rulesList = document.getElementById('pw-rules');
    var regPw = document.querySelector('form[data-form="register"] input[name="password"]');
    if (rulesList && regPw) {
        regPw.addEventListener('input', function () {
            var c = passwordChecks(regPw.value);
            Array.prototype.forEach.call(rulesList.querySelectorAll('li'), function (li) {
                li.classList.toggle('ok', !!c[li.getAttribute('data-rule')]);
            });
        });
    }

    // Show / Hide password buttons
    Array.prototype.forEach.call(document.querySelectorAll('.pw-toggle'), function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle'));
            var hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            btn.textContent = hidden ? 'Hide' : 'Show';
            btn.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });
    });

    // character counter for the contact message
    var msg = document.getElementById('message');
    var counter = document.getElementById('msg-count');
    if (msg && counter) {
        msg.addEventListener('input', function () { counter.textContent = msg.value.length; });
    }

    // "Account created" note on the Sign In page
    if (/[?&]registered=1/.test(window.location.search)) {
        var note = document.getElementById('registered-note');
        if (note) note.hidden = false;
    }
})();
