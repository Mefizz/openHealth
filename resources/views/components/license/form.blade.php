@props(['license' => null, 'readonly' => false, 'action' => null, 'method' => 'POST', 'formId' => 'license-form'])

<form :id="$formId" :action="$action" method="POST" {{ $readonly ? 'inert' : '' }}>
    @csrf
    @if(!in_array(strtoupper($method), ['GET','POST'])) @method($method) @endif
    <fieldset @disabled($readonly)>
        {{-- TODO: fields (leave as comment for now) --}}
    </fieldset>
</form>
