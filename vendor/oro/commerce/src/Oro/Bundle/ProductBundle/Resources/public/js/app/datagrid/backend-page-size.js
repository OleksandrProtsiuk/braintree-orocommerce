define(function(require) {
    'use strict';

    var BackendPageSize;
    var $ = require('jquery');
    var _ = require('underscore');
    var PageSize = require('orodatagrid/js/datagrid/page-size');

    BackendPageSize = PageSize.extend({
        /** @property */
        themeOptions: {
            optionPrefix: 'backendpagesize',
            el: '[data-grid-pagesize]'
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var $select = this.$el.find('[data-grid-pagesize-selector]');
            var currentSizeLabel = _.filter(
                this.items,
                _.bind(
                    function(item) {
                        return item.size === undefined ?
                        this.collection.state.pageSize === item : this.collection.state.pageSize === item.size;
                    },
                    this
                )
            );

            $select
                .find('option')
                .removeAttr('selected', false)
                .filter('[value=' + currentSizeLabel[0] +']')
                .attr('selected', true);

            $select.inputWidget('val', currentSizeLabel[0]);

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }

    });
    return BackendPageSize;
});
