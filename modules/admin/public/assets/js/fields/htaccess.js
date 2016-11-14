(function($) {

    $(document).ready(init);

    function init() {

        $('.widget-type-htaccess > .widget-content').each(function() {

            var $wrap = $(this),
                $actionsTop = $wrap.find('> .widget-actions-top'),
                $footer = $wrap.find('> .widget-footer'),
                $table = $wrap.find('> table').eq(0),
                $items = $table.find('> tbody > tr.item'),
                $template = $table.find('> tbody > tr.item-template').eq(0),
                $noItemsRow = $table.find('> tbody > tr.no-items-row'),
                name = $table.attr('data-field-name'),
                settings = (typeof(field_settings[name]) != 'undefined') ? field_settings[name] : {},
                inc = $items.length,
                sortable = $table.hasClass('sortable'),
                positions = {};

            // Don't run on temporary fields...
            if (name.indexOf('__TEMP__') >= 0) { return; }

            $footer.find('.btn-add').click(addItem);
            $actionsTop.find('.btn-add').click(addItem);
            $noItemsRow.find('.btn-add').click(addItem);

            $items.find('.btn-remove').click(removeButtonHandler);

            if (sortable) { initSorting(); }
            update();

            function addItem() {

                var $item = $template.clone();

                $item.addClass('item'+(sortable ? ' draggable' : '')).removeClass('item-template').find('[data-name]').each(function() {

                    var $el = $(this);
                    var dataName = $el.data('name');

                    $el.attr('name', dataName.replace('__TEMP__', '').replace('__NUM__', inc));

                });

                if ($items.length == 0) {
                    $table.append($item);
                } else {
                    $item.insertAfter($items.last());
                }

                $item.find('.btn-remove').click(removeButtonHandler);
                $items = $table.find('> tbody > tr.item');
                inc++;

                // So all the various plugins can run their magic on relevant inputs...
                $(window).trigger('cmf.newform', { 'wrap':$item });

                update();
                return false;
            }

            function removeButtonHandler() {

                if (!confirm(_('admin.messages.item_delete_confirm'))) { return false; }
                var $item = $(this).parents('.item').eq(0);
                $item.remove();
                $items = $table.find('> tbody > tr.item');

                update();

                return false;

            }

            function update() {

                if ($items.length > 0) {
                    $table.addClass('populated');
                } else {
                    $table.removeClass('populated');
                }

                updatePositions();
            }

            function updatePositions() {

                if (!sortable) { return; }

                $items = $table.find('> tbody > tr.item');

                $items.each(function(i, el) {
                    $(el).find('[data-name]').each(function(j, el) {
                        var dataName = $(el).data('name') || '';
                        $(el).attr('name', dataName.replace('__TEMP__', '').replace('__NUM__', i));
                    });
                });
            }

            function initSorting() {

                var $tableBody = $table.find("> tbody"),
                    $rows = $tableBody.find('> tr.item'),
                    $body = $('body'),
                    tableName = settings['target_table'],
                    saveAll = settings['save_all'];

                $tableBody.sortable({ helper:fixHelper, handle:'.handle' });

                $tableBody.sortable('option', 'start', function (evt, ui) {
                    ui.item.trigger('cmf.dragstart');
                    $body.addClass('sorting');
                });

                $tableBody.sortable('option', 'stop', function(evt, ui) {
                    ui.item.trigger('cmf.dragstop');
                    $body.removeClass('sorting');
                    updatePositions();
                });

                updatePositions()
            }

        });

    }

})(jQuery);