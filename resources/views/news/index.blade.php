@extends('layouts.main')
@section('title')
    {{ __('News') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h4 class="mb-0">@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                @can('news-create')
                    <a class="btn btn-primary" href="{{ route('news.create') }}">+ {{ __('Add News') }}</a>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-borderless table-striped" aria-describedby="news-table"
                            id="table_list" data-toggle="table" data-url="{{ route('news.show', 0) }}"
                            data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-search-align="right" data-toolbar="#toolbar" data-show-columns="true"
                            data-show-refresh="true" data-trim-on-search="false" data-responsive="true"
                            data-sort-name="id" data-sort-order="desc" data-pagination-successively-size="3"
                            data-query-params="queryParams" data-table="news" data-mobile-responsive="true"
                            data-show-export="false"
                            data-export-options='{"fileName": "news-list","ignoreColumn": ["operate"]}'
                            data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col" data-field="id" data-align="center" data-sortable="true">
                                        {{ __('ID') }}
                                    </th>
                                    <th scope="col" data-field="city_name" data-align="center" data-sortable="true">
                                        {{ __('City') }}
                                    </th>
                                    <th scope="col" data-field="cover_image" data-align="center"
                                        data-formatter="imageFormatter">
                                        {{ __('Cover Image') }}
                                    </th>
                                    <th scope="col" data-field="english_html" data-align="center">
                                        {{ __('English Content') }}
                                    </th>
                                    <th scope="col" data-field="urdu_html" data-align="center">
                                        {{ __('Urdu Content') }}
                                    </th>
                                    <th scope="col" data-field="created_by_name" data-align="center"
                                        data-sortable="true">
                                        {{ __('Created By') }}
                                    </th>
                                    <th scope="col" data-field="updated_by_name" data-align="center"
                                        data-sortable="true">
                                        {{ __('Updated By') }}
                                    </th>
                                    <th scope="col" data-field="created_at" data-align="center" data-sortable="true">
                                        {{ __('Created At') }}
                                    </th>
                                    <th scope="col" data-field="updated_at" data-align="center" data-sortable="true">
                                        {{ __('Updated At') }}
                                    </th>
                                    @canany(['news-update', 'news-delete'])
                                        <th scope="col" data-escape="false" data-field="operate">
                                            {{ __('Action') }}
                                        </th>
                                    @endcanany
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
