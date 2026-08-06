@extends('layouts.main')
@section('title')
    {{__("Create Categories")}}
@endsection

@section('css')
    <style>
        html, body, #app, #main, #main-content,
        .category-form-page,
        .category-form-page > .row,
        .category-form-page > .row > form,
        .category-form-page .card,
        .parent-category-field {
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }
        html, body, #app, #main, #main-content, .category-form-page {
            overflow-x: hidden;
        }
        .category-form-page > .row {
            width: 100%;
            margin-right: 0;
            margin-left: 0;
        }
        .category-form-page > .row > form {
            width: 100%;
            padding-right: 0;
            padding-left: 0;
        }
        .parent-category-field {
            position: relative;
        }
        .parent-category-field .select2-container,
        .parent-category-field .select2-dropdown {
            width: 100% !important;
            max-width: 100%;
        }
        .select2-container--open .select2-dropdown {
            box-sizing: border-box;
            overflow-x: hidden;
        }
        .select2-container--open .select2-results__option {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section category-form-page">
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('category.index') }}">< {{__("Back to All Categories")}} </a>
        </div>
        <div class="row">
            <form action="{{ route('category.store') }}" method="POST" data-parsley-validate enctype="multipart/form-data">
                @csrf
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">{{__("Add Category")}}</div>

                        <div class="card-body mt-2">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                @foreach($languages as $key => $lang)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link @if($key == 0) active @endif" id="tab-{{ $lang->id }}" data-bs-toggle="tab" data-bs-target="#lang-{{ $lang->id }}" type="button" role="tab">
                                            {{ $lang->name }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content mt-3">
                                @foreach($languages as $key => $lang)
                                    <div class="tab-pane fade @if($key == 0) show active @endif" id="lang-{{ $lang->id }}" role="tabpanel">
                                        <input type="hidden" name="languages[]" value="{{ $lang->id }}">

                                        <div class="form-group">
                                            <label>{{ __('Name') }} ({{ $lang->name }})</label>
                                            <input type="text" 
                                                name="name[{{ $lang->id }}]" 
                                                class="form-control" 
                                                value=""
                                                data-parsley-maxlength="30"
                                                maxlength="30"
                                                data-parsley-maxlength-message="{{ __('Name cannot exceed 30 characters.') }}"
                                                @if($lang->id == 1) data-parsley-required="true" @endif>
                                        </div>

                                        <div class="form-group">
                                            <label>{{ __('Description') }} ({{ $lang->name }})</label>
                                            <textarea name="description[{{ $lang->id }}]" class="form-control" cols="10" rows="5"></textarea>
                                        </div>

                                        @if($lang->id == 1)
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group mandatory">
                                                        <label for="category_slug" class="form-label">{{ __('Slug') }} <small>{{__('(English Only)')}}</small></label>
                                                        <input type="text" name="slug" id="category_slug" class="form-control" data-parsley-pattern="^[a-zA-Z0-9\-_]+$"
                                                            data-parsley-pattern-message="{{ __('Slug must be only English letters, numbers, hyphens (-) or underscores (_).') }}" placeholder="auto-generated if blank">
                                                        <label>
                                                            <small class="text-danger">{{ __('Note: Slug must be in English letters, numbers, hyphens (-) or underscores (_). No spaces or special characters.') }}</small>
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group parent-category-field">
                                                        <label for="p_category" class="form-label">{{ __('Parent Category') }}</label>
                                                        <select name="parent_category_id" id="p_category" class="form-select form-control select2" data-placeholder="{{__('Select Category')}}">
                                                            <option value="">{{__('Select a Category')}}</option>
                                                            @include('category.dropdowntree', ['categories' => $categories])
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="col-md-12 form-group mandatory">
                                                        <label for="Field Name" class="mandatory form-label">{{ __('Image') }}</label>
                                                        <input type="file" name="image" id="image" class="form-control" data-parsley-required="true" accept=".jpg,.jpeg,.png">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="row mt-3">
                                                        <div class="col-md-4">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="status" id="status" value="0">
                                                                <input class="form-check-input status-switch" type="checkbox" role="switch" id="statusSwitch">
                                                                <label class="form-check-label" for="statusSwitch">{{ __('Active') }}</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="is_job_category" id="is_job_category" value="0">
                                                                <input class="form-check-input status-switch" type="checkbox" role="switch" id="jobCategorySwitch">
                                                                <label class="form-check-label" for="jobCategorySwitch">{{ __('Job Category') }}</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-check form-switch">
                                                                <input type="hidden" name="price_optional" id="price_optional" value="0">
                                                                <input class="form-check-input status-switch" type="checkbox" role="switch" id="priceOptionalSwitch">
                                                                <label class="form-check-label" for="priceOptionalSwitch">{{ __('Price Optional') }}</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 text-end">
                        <input type="submit" class="btn btn-primary" value="{{__("Save and Back")}}">
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[data-parsley-validate]');
        if (!form) return;

        const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
        form.addEventListener('submit', function(e) {
            // Use Parsley to check validity if initialized
            if (typeof $(form).parsley === 'function') {
                if (!$(form).parsley().isValid()) {
                    // If invalid, do NOT disable the button, allow user to correct form
                    return;
                }
            }
            // Disable submit button on valid submission
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.value = '{{ __("Saving...") }}';
            }
        });
    });
</script>




