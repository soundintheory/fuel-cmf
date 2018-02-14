(function ($) {

    $(document).on('ready', function () {
        var preview = new PreviewPassword('form_email', 'password-viewer');
    });

    PreviewPassword = function (input, btn) {
        this.input = document.getElementById(input);
        this.btn = document.getElementById(btn);
        this.init();
    };

    PreviewPassword.prototype.init = function () {

        document.getElementById('form_email').setAttribute('type', 'password');

        $(this.btn).click(function () {
            if ($(this.input).is(':password')) {
                this.show();
            } else if ($(this.input).is(':text')) {
                this.hide();
            }
        }.bind(this));

    };

    PreviewPassword.prototype.show = function () {
        this.input.setAttribute('type', 'text');
        this.btn.classList.remove('fa-eye');
        this.btn.classList.add('fa-eye-slash');

        if (this.input.classList.contains('fa-eye-slash')) {
            setInterval(function () {
                this.hide();
            }.bind(this), 10000);
        }

    };

    PreviewPassword.prototype.hide = function () {
        this.input.setAttribute('type', 'password');
        this.btn.classList.remove('fa-eye-slash');
        this.btn.classList.add('fa-eye');
    };

})(jQuery);