@extends('livewire.employee-request.base')

@section('form-content')
    @include('livewire.employee-request._parts._employee', ['editMode' => true])
    @include('livewire.employee-request._parts._documents', ['editMode' => true])

    <template x-if="isDoctor">
        <div>
            @include('livewire.employee-request._parts._education', ['editMode' => true])
            @include('livewire.employee-request._parts._specialities', ['editMode' => true])
            @include('livewire.employee-request._parts._science_degree', ['editMode' => true])
            @include('livewire.employee-request._parts._qualifications', ['editMode' => true])
        </div>
    </template>
@endsection

@section('form-buttons')
    <button type="button" class="button-minor" wire:click="cancel">
        {{ __('forms.cancel') }}
    </button>

    <button type="submit" class="button-primary">
        {{ __('forms.update_request') }}
    </button>
@endsection
