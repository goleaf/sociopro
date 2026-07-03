@php
    $selectedCategory = old('category', $job?->category_id);
    $status = old('status', $job?->status ?? '1');
    $isPublished = old('is_published', $job?->is_published ?? false);
@endphp

<div class="mb-3">
    <label for="title" class="form-label eForm-label">{{ get_phrase('Title') }}</label>
    <input type="text" class="form-control eForm-control" id="title" name="title" value="{{ old('title', $job?->title) }}">
</div>
<div class="mb-3">
    <label for="category" class="form-label eForm-label">{{ get_phrase('Category') }}</label>
    <select name="category" id="category" class="form-select eForm-control">
        <option value="Select a category">{{ get_phrase('Select a category') }}</option>
        @foreach ($jobCategories as $category)
            <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="starting_salary_range" class="form-label eForm-label">{{ get_phrase('Starting Salary Range') }}</label>
        <input type="text" class="form-control eForm-control" id="starting_salary_range" name="starting_salary_range" value="{{ old('starting_salary_range', $job?->starting_salary_range) }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="ending_salary_range" class="form-label eForm-label">{{ get_phrase('Ending Salary Range') }}</label>
        <input type="text" class="form-control eForm-control" id="ending_salary_range" name="ending_salary_range" value="{{ old('ending_salary_range', $job?->ending_salary_range) }}">
    </div>
</div>
<div class="mb-3">
    <label for="company" class="form-label eForm-label">{{ get_phrase('Company') }}</label>
    <input type="text" class="form-control eForm-control" id="company" name="company" value="{{ old('company', $job?->company) }}">
</div>
<div class="mb-3">
    <label for="type" class="form-label eForm-label">{{ get_phrase('Type') }}</label>
    <input type="text" class="form-control eForm-control" id="type" name="type" value="{{ old('type', $job?->type) }}">
</div>
<div class="mb-3">
    <label for="location" class="form-label eForm-label">{{ get_phrase('Location') }}</label>
    <input type="text" class="form-control eForm-control" id="location" name="location" value="{{ old('location', $job?->location) }}">
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="start_date" class="form-label eForm-label">{{ get_phrase('Start Date') }}</label>
        <input type="date" class="form-control eForm-control" id="start_date" name="start_date" value="{{ old('start_date', optional($job?->start_date)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="end_date" class="form-label eForm-label">{{ get_phrase('End Date') }}</label>
        <input type="date" class="form-control eForm-control" id="end_date" name="end_date" value="{{ old('end_date', optional($job?->end_date)->format('Y-m-d')) }}">
    </div>
</div>
<div class="mb-3">
    <label for="status" class="form-label eForm-label">{{ get_phrase('Status') }}</label>
    <select name="status" id="status" class="form-select eForm-control">
        <option value="1" @selected((string) $status === '1')>{{ get_phrase('Active') }}</option>
        <option value="0" @selected((string) $status === '0')>{{ get_phrase('Pending') }}</option>
    </select>
</div>
<div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1" @checked((bool) $isPublished)>
    <label for="is_published" class="form-check-label">{{ get_phrase('Published') }}</label>
</div>
<div class="mb-3">
    <label for="image" class="form-label eForm-label">{{ get_phrase('Thumbnail') }}</label>
    <input type="file" class="form-control eForm-control" id="image" name="image">
</div>
<div class="mb-3">
    <label for="description" class="form-label eForm-label">{{ get_phrase('Description') }}</label>
    <textarea id="description" name="description" class="form-control eForm-control">{{ old('description', $job?->description) }}</textarea>
</div>
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
