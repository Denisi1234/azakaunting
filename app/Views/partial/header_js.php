<?php
/** @var array $config */
?>

<script type="text/javascript">
    // Live clock
    const clock_tick = function clock_tick() {
        setInterval('update_clock();', 1000);
    }

    // Start the clock immediately
    clock_tick();

    const update_clock = function update_clock() {
        document.getElementById('liveclock').innerHTML = moment().format("<?= dateformat_momentjs($config['dateformat'] . ' ' . $config['timeformat']) ?>");
    }

    const notify = $.notify;

    $.notify = function(content, options) {
        const message = typeof content === "object" ? content.message : content;
        const sanitizedMessage = DOMPurify.sanitize(message, { ALLOWED_TAGS: [], ALLOWED_ATTR: [] });
        return notify(sanitizedMessage, options);
    };

    $.notifyDefaults({
        placement: {
            align: "<?= esc($config['notify_horizontal_position'], 'js') ?>",
            from: "<?= esc($config['notify_vertical_position'], 'js') ?>"
        }
    });

    const csrf_token = function() {
        return "<?= esc(csrf_hash(), 'js') ?>";
    };

    const csrf_form_base = function() {
        return {
            <?= esc(config('Security')->tokenName, 'js') ?>: function() {
                return csrf_token()
            }
        }
    };

    const setup_csrf_token = function() {
        $('input[name="<?= esc(config('Security')->tokenName, 'js') ?>"]').val(csrf_token());
    };

    const ajax = $.ajax;

    $.ajax = function() {
        let args = arguments[0];
        if (args['type'] && args['type'].toLowerCase() == 'post' && csrf_token()) {
            if (typeof args['data'] === 'string') {
                args['data'] += '&' + $.param(csrf_form_base());
            } else {
                args['data'] = $.extend(args['data'], csrf_form_base());
            }
        }

        return ajax.apply(this, arguments);
    };

    $(document).ajaxComplete(setup_csrf_token);
    $(document).ajaxError(function(event, jqXHR, settings) {
        if (jqXHR.status === 401) {
            window.location.href = '<?= site_url('login') ?>';
        }
    });
    $(document).ready(function() {
        const prepareMobileRegisterCards = function() {
            const registerTable = document.querySelector('#register');
            const tableHeaders = registerTable ? Array.from(registerTable.querySelectorAll('thead th')) : [];
            const cartRows = registerTable ? registerTable.querySelectorAll('tbody tr') : [];

            cartRows.forEach(function(row) {
                const cells = Array.from(row.children).filter(function(cell) {
                    return cell.tagName === 'TD';
                });

                if (cells.some(function(cell) { return cell.colSpan > 1; })) {
                    row.classList.add('mobile-cart-detail-row');
                    return;
                }

                row.classList.remove('mobile-cart-detail-row');
                cells.forEach(function(cell, index) {
                    const label = tableHeaders[index] && tableHeaders[index].textContent.trim();
                    if (label) {
                        cell.dataset.mobileLabel = label;
                    }
                });
            });
        };

        prepareMobileRegisterCards();

        const registerTable = document.querySelector('#register');
        const cartContents = registerTable && registerTable.querySelector('tbody');
        if (cartContents) {
            new MutationObserver(prepareMobileRegisterCards).observe(cartContents, { childList: true });
        }

        $("#logout").click(function(event) {
            event.preventDefault();
            $.ajax({
                url: "<?= site_url('home/logout'); ?>",
                data: {
                    "<?= esc(config('Security')->tokenName, 'js'); ?>": csrf_token()
                },
                success: function() {
                    window.location.href = '<?= site_url(); ?>';
                },
                method: "POST"
            });
        });

        // Mobile drawer handlers
        const $drawer = $("#ospos-sidebar-collapse");
        const $backdrop = $("#ospos-drawer-backdrop");
        const $hamburger = $("#mobile-hamburger-btn");

        if ($(window).width() >= 992) {
            $drawer.removeAttr("aria-hidden");
        }

        function openDrawer() {
            $("body").addClass("drawer-open");
            $drawer.addClass("drawer-open");
            $hamburger.addClass("is-active").attr({"aria-expanded": "true", "aria-label": "Close navigation menu"});
            $drawer.attr("aria-hidden", "false");
            $drawer.find(".drawer-close-btn").trigger("focus");
        }

        function closeDrawer() {
            $("body").removeClass("drawer-open");
            $drawer.removeClass("drawer-open");
            $hamburger.removeClass("is-active").attr({"aria-expanded": "false", "aria-label": "Open navigation menu"});
            $drawer.attr("aria-hidden", "true");
        }

        $hamburger.on("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            if ($drawer.hasClass("drawer-open")) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        $backdrop.add(".drawer-close-btn").on("click", function(e) {
            e.preventDefault();
            closeDrawer();
            $hamburger.trigger("focus");
        });

        $(document).on("keydown", function(e) {
            if (e.key === "Escape" && $drawer.hasClass("drawer-open")) {
                closeDrawer();
                $hamburger.trigger("focus");
            }
        });

        $(window).on("resize", function() {
            if ($(window).width() >= 992) {
                closeDrawer();
                $drawer.removeAttr("aria-hidden");
            } else if (!$drawer.hasClass("drawer-open")) {
                $drawer.attr("aria-hidden", "true");
            }
        });

        $drawer.find(".ospos-sidebar-nav a").on("click", function(e) {
            if ($(window).width() < 992) {
                e.preventDefault();
                const destination = this.href;
                closeDrawer();
                window.location.assign(destination);
            }
        });
    });

    const submit = $.fn.submit;

    $.fn.submit = function() {
        setup_csrf_token();
        submit.apply(this, arguments);
    };
</script>
