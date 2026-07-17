function placeRequestFiltersInTableToolbar(tableSelector, tabsSelector) {
    const table = $(tableSelector);
    const tabs = $(tabsSelector);

    const placeTabs = function () {
        const toolbar = table.closest('.bootstrap-table').find('.fixed-table-toolbar').first();
        if (toolbar.length && !tabs.parent().is(toolbar)) {
            tabs.prependTo(toolbar);
        }
    };

    placeTabs();
    setTimeout(placeTabs, 0);
    table.on('post-header.bs.table reset-view.bs.table', placeTabs);
}
