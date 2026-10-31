<script type="text/javascript">
    $(document).ready(function() {
        function loadNotifications() {
            $.get('<?= base_url('notifications') ?>', function(response) {
                var notifications = response.notifications || [];
                var list = $('#notifications_list');
                list.empty();

                if (notifications.length === 0) {
                    list.append('<li><a href="#" class="text-muted"><span class="glyphicon glyphicon-inbox"></span> No notifications</a></li>');
                } else {
                    $.each(notifications, function(index, n) {
                        var icon = n.type === 'success' ? 'glyphicon-ok-circle' :
                                   n.type === 'warning' ? 'glyphicon-warning-sign' :
                                   n.type === 'danger' ? 'glyphicon-exclamation-sign' : 'glyphicon-info-sign';
                        var readClass = n.is_read == 1 ? 'notification-read' : 'notification-unread';
                        var link = n.link ? n.link : '#';
                        var li = $(
                            '<li class="' + readClass + '">' +
                            '<a href="' + link + '" class="notification-item" data-id="' + n.notification_id + '">' +
                            '<span class="glyphicon ' + icon + '"></span> ' +
                            '<strong>' + n.title + '</strong><br>' +
                            '<small class="text-muted">' + n.message + '</small><br>' +
                            '<small class="text-muted">' + n.created_at + '</small>' +
                            '</a></li>'
                        );
                        list.append(li);
                    });
                }

                updateUnreadCount();
            }, 'json');
        }

        function updateUnreadCount() {
            $.get('<?= base_url('notifications/unreadCount') ?>', function(response) {
                var count = response.count || 0;
                $('#notifications_count').text(count);
                if (count > 0) {
                    $('#notifications_count').show();
                } else {
                    $('#notifications_count').hide();
                }
            }, 'json');
        }

        loadNotifications();

        // Poll for new notifications every 60 seconds
        setInterval(updateUnreadCount, 60000);

        // Mark as read on click
        $(document).on('click', '.notification-item', function() {
            var id = $(this).data('id');
            $.post('<?= base_url('notifications/markRead/') ?>' + id, function() {
                updateUnreadCount();
            }, 'json');
        });

        // Mark all as read
        $('#mark_all_read').click(function(e) {
            e.preventDefault();
            $.post('<?= base_url('notifications/markAllRead') ?>', function() {
                loadNotifications();
            }, 'json');
        });
    });
</script>

<style>
    .notification-unread { background: #f0f7ff; }
    .notification-read { opacity: 0.7; }
    .notification-item { white-space: normal; max-width: 300px; }
    #notifications_count { background: #e74c3c; }
</style>

<div class="dropdown">
    <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
        <span class="glyphicon glyphicon-bell"></span>
        <span class="badge" id="notifications_count" style="display:none;">0</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right" id="notifications_list" style="min-width: 320px; max-height: 400px; overflow-y: auto;">
        <li><a href="#" id="mark_all_read"><span class="glyphicon glyphicon-ok"></span> Mark all as read</a></li>
        <li class="divider"></li>
    </ul>
</div>