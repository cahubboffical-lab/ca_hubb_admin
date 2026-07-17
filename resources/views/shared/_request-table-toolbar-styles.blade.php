.fixed-table-toolbar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    min-height: 52px;
}

.fixed-table-toolbar .float-left,
.fixed-table-toolbar .float-right,
.fixed-table-toolbar .search,
.fixed-table-toolbar .columns {
    float: none !important;
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}

.request-status-toolbar {
    order: 1;
    margin: 0 !important;
    flex-wrap: nowrap;
}

.request-status-toolbar .nav-link {
    padding: 0.55rem 1rem;
    white-space: nowrap;
}

.fixed-table-toolbar .search {
    order: 2;
    margin-left: auto !important;
}

.fixed-table-toolbar .columns {
    order: 3;
}

@media (max-width: 767.98px) {
    .request-status-toolbar {
        flex-basis: 100%;
        overflow-x: auto;
    }

    .fixed-table-toolbar .search {
        margin-left: 0 !important;
        flex: 1 1 220px;
    }
}
