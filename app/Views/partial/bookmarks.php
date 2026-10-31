<?php
/**
 * @var int $person_id
 */
?>

<script type="text/javascript">
    $(document).ready(function() {
        $.get('<?= base_url('bookmarks') ?>', function(response) {
            var bookmarks = response.bookmarks || [];
            var list = $('#bookmarks_list');
            list.empty();

            $.each(bookmarks, function(index, bookmark) {
                var icon = bookmark.icon ? bookmark.icon : 'glyphicon-link';
                var li = $(
                    '<li><a href="' + bookmark.url + '" target="_blank">' +
                    '<span class="glyphicon ' + icon + '"></span> ' +
                    bookmark.title +
                    '<button class="btn btn-link btn-xs bookmark-remove" data-id="' + bookmark.bookmark_id + '" title="Remove">' +
                    '<span class="glyphicon glyphicon-remove"></span></button>' +
                    '</a></li>'
                );
                list.append(li);
            });

            $('#bookmarks_count').text(bookmarks.length);

            $('.bookmark-remove').click(function() {
                var bookmarkId = $(this).data('id');
                $.post('<?= base_url('bookmarks/remove/') ?>' + bookmarkId, function() {
                    $.get('<?= base_url('bookmarks') ?>', function(response) {
                        var bookmarks = response.bookmarks || [];
                        var list = $('#bookmarks_list');
                        list.empty();
                        $.each(bookmarks, function(index, bookmark) {
                            var icon = bookmark.icon ? bookmark.icon : 'glyphicon-link';
                            var li = $(
                                '<li><a href="' + bookmark.url + '" target="_blank">' +
                                '<span class="glyphicon ' + icon + '"></span> ' +
                                bookmark.title +
                                '<button class="btn btn-link btn-xs bookmark-remove" data-id="' + bookmark.bookmark_id + '" title="Remove">' +
                                '<span class="glyphicon glyphicon-remove"></span></button>' +
                                '</a></li>'
                            );
                            list.append(li);
                        });
                        $('#bookmarks_count').text(bookmarks.length);
                    }, 'json');
                }, 'json');
            });
        }, 'json');
    });
</script>

<div class="dropdown">
    <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
        <span class="glyphicon glyphicon-bookmark"></span>
        <span class="hidden-xs"> <?= lang('Bookmarks.title') ?> </span>
        <span class="badge" id="bookmarks_count">0</span>
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right" id="bookmarks_list">
        <li><a href="#" id="bookmark_add" data-toggle="modal" data-target="#bookmarkModal">
            <span class="glyphicon glyphicon-plus"></span> <?= lang('Bookmarks.title') ?>
        </a></li>
    </ul>
</div>

<div class="modal fade" id="bookmarkModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"> <?= lang('Bookmarks.title') ?> </h4>
            </div>
            <div class="modal-body">
                <form id="bookmarkForm">
                    <div class="form-group">
                        <label for="bookmark_title"> <?= lang('Bookmarks.title') ?> *</label>
                        <input type="text" name="title" id="bookmark_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="bookmark_url"> URL *</label>
                        <input type="url" name="url" id="bookmark_url" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="bookmark_icon"> Icon (glyphicon class)</label>
                        <input type="text" name="icon" id="bookmark_icon" class="form-control" placeholder="glyphicon-link">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="bookmark_submit">
                    <span class="glyphicon glyphicon-save"></span> Save
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#bookmarkModal').on('shown.bs.modal', function() {
            $('#bookmark_title').focus();
        });

        $('#bookmark_submit').click(function() {
            var title = $('#bookmark_title').val();
            var url = $('#bookmark_url').val();
            var icon = $('#bookmark_icon').val();

            $.post('<?= base_url('bookmarks/add') ?>', {
                title: title,
                url: url,
                icon: icon
            }, function(response) {
                if (response.success) {
                    $('#bookmarkModal').modal('hide');
                    $('#bookmarkForm')[0].reset();
                    $.get('<?= base_url('bookmarks') ?>', function(r) {
                        var bookmarks = r.bookmarks || [];
                        var list = $('#bookmarks_list');
                        list.empty();
                        $.each(bookmarks, function(index, bookmark) {
                            var ic = bookmark.icon ? bookmark.icon : 'glyphicon-link';
                            list.append(
                                '<li><a href="' + bookmark.url + '" target="_blank">' +
                                '<span class="glyphicon ' + ic + '"></span> ' +
                                bookmark.title +
                                '<button class="btn btn-link btn-xs bookmark-remove" data-id="' + bookmark.bookmark_id + '" title="Remove">' +
                                '<span class="glyphicon glyphicon-remove"></span></button>' +
                                '</a></li>'
                            );
                        });
                        $('#bookmarks_count').text(bookmarks.length);
                    }, 'json');
                }
            }, 'json');
        });
    });
</script>