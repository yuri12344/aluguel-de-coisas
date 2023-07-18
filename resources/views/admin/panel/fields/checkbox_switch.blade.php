{{-- checkbox field --}}
<div @include('admin.panel.inc.field_wrapper_attributes') >
	@include('admin.panel.fields.inc.translatable_icon')
    <div class="form-check form-switch" style="margin-top: 30px;">
		<input type="hidden" name="{{ $field['name'] }}" value="0">
		<input type="checkbox" value="1" name="{{ $field['name'] }}"
			@if (isset($field['value']))
				@php
					$isFieldChecked = (str_ends_with($field['name'], '_at'))
						? (!empty($field['value']) || !empty(old($field['name'])))
						: (((int) $field['value'] == 1 || old($field['name']) == 1) && old($field['name']) !== '0');
				@endphp
				
				@if ($isFieldChecked)
					checked="checked"
				@endif
			@elseif (isset($field['default']) && $field['default'])
				checked="checked"
			@endif
			
			@if (isset($field['attributes']) && !empty($field['attributes']))
				@foreach ($field['attributes'] as $attribute => $value)
					@if ($attribute == 'class')
						{{ $attribute }}="form-check-input {{ $value }}"
					@elseif ($attribute == 'style')
						{{ $attribute }}="cursor: pointer; {{ $value }}"
					@else
						{{ $attribute }}="{{ $value }}"
					@endif
				@endforeach
				@if (!array_key_exists('class', $field['attributes']))
					class="form-check-input"
				@endif
				@if (!array_key_exists('style', $field['attributes']))
					style="cursor: pointer;"
				@endif
			@else
				class="form-check-input" style="cursor: pointer;"
			@endif
		>
		<label class="form-check-label fw-bolder">
			{!! $field['label'] !!}
		</label>
		
		{{-- HINT --}}
		@if (isset($field['hint']))
			<div class="form-text">{!! $field['hint'] !!}</div>
		@endif
    </div>
</div>
