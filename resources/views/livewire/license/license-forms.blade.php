<body class="bg-white dark:bg-gray-800 min-h-screen text-gray-900 dark:text-white">
<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('Нова додаткова ліцензія') }}</x-slot>
    </x-section-navigation>
    <form class="form">
        <div class="form-row-2">
            <div class="form-group">
                <input type="text" name="licenseKind" id="licenseKind" class="peer input dark:text-gray-400" value="Додаткова" placeholder=" " required />
                <label for="licenseKind" class="label">Вид ліцензії</label>
            </div>
            <div class="form-group">
                <input type="text" name="OrderNumber" id="OrderNumber" class="peer input" placeholder=" " required />
                <label for="OrderNumber" class="label">Номер наказу</label>
            </div>
        </div>
        <div class="form-row" x-data="{
            open: false,
            selected: '',
            choose(option) {
                this.selected = option;
                this.open = false;
            }
        }">
            <div class="relative w-full">
                <div class="input-select peer cursor-pointer whitespace-normal break-words min-h-[48px] px-3 py-2 pr-10"
                     x-on:click="open = !open"
                     :class="{ 'ring-1 ring-blue-500 border-blue-500': open }">
                    <span x-text="selected || 'Оберіть тип ліцензії'"></span>
                    <span class="absolute right-3 top-1/2 w-2 h-2 border-r-2 border-b-2 border-gray-500 dark:border-gray-400 transform -translate-y-1/2 rotate-45 pointer-events-none"></span>
                </div>

                <ul x-show="open" x-transition x-cloak class="dropdown-panel w-full max-h-60 overflow-auto z-10">
                    @foreach ($dictionaries['LICENSE_TYPE'] ?? [] as $key => $label)
                        <li>
                            <button type="button"
                                    x-text="'{{ $label }}'"
                                    x-on:click="choose('{{ $label }}')"
                                @class([
                                    'text-left text-sm whitespace-normal break-words px-3 py-2 w-full text-start',
                                    'rounded-t-md' => $loop->first,
                                    'rounded-b-md' => $loop->last,
                                ])>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <label class="label">Тип ліцензії</label>
                <input type="hidden" name="licenseType" :value="selected">
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input type="text" name="IssuedTheLicense" id="IssuedTheLicense" class="peer input" placeholder=" " required />
                <label for="IssuedTheLicense" class="label">Ким видано</label>
            </div>
            <div class="form-group">
                <input type="text" name="licensedActivity" id="licensedActivity" class="peer input" placeholder=" " required />
                <label for="licensedActivity" class="label">Напрям діяльності, що ліцензовано</label>
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input type="text" name="licenseSeriesNumber" id="licenseSeriesNumber" class="peer input" placeholder=" " required />
                <label for="LicenseSeriesNumber" class="label">Серія та/або номер ліцензії</label>
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input type="text" name="dateOfLicenseIssuance" id="dateOfLicenseIssuance" class="peer input pl-10 appearance-none datepicker-input" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="dateOfLicenseIssuance" class="wrapped-label">Дата видачі ліцензії</label>
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group datepicker-wrapper relative w-full">
                <input type="text" name="dateOfLicenseStartDate" id="dateOfLicenseStartDate" class="peer input pl-10 appearance-none datepicker-input" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="dateOfLicenseStartDate" class="wrapped-label">Дата початку дії ліцензії</label>
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input type="text" name="dateOfLicenseExpiry" id="dateOfLicenseExpiry" class="peer input pl-10 appearance-none datepicker-input" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="dateOfLicenseExpiry" class="wrapped-label">Дата завершення дії ліцензії</label>
            </div>
        </div>
        <div class="flex justify-start gap-4 mt-10">
            <button type="button" class="button-minor">
                Скасувати
            </button>
            <button type="submit" class="button-primary">
                Додати ліцензію
            </button>
        </div>
    </form>
</div>
</div>
</body>
