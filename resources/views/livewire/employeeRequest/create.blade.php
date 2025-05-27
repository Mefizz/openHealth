@extends('livewire.employee-request.base')

@section('form-content')
    @include('livewire.employee-request._parts._employee')
    @include('livewire.employee-request._parts._documents')

    <template x-if="isDoctor">
        <div>
            @include('livewire.employee-request._parts._education')
            @include('livewire.employee-request._parts._specialities')
            @include('livewire.employee-request._parts._science_degree')
            @include('livewire.employee-request._parts._qualifications')
        </div>
    </template>
@endsection

@section('form-buttons')
    <button type="button" class="button-minor" wire:click="cancel">
        {{ __('forms.cancel') }}
    </button>

    <button type="submit" class="button-primary">
        {{ __('forms.create_request') }}
    </button>

    <button
        type="button"
        class="button-primary"
        x-bind:disabled="!isFormValid"
        @click="prepareForSigning"
    >
        {{ __('forms.send_for_approval') }}
    </button>
@endsection
