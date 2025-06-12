<body class="bg-white dark:bg-gray-800 min-h-screen text-gray-900 dark:text-white">
    <div>
        <x-section-navigation class="breadcrumb-form">
            <x-slot name="title">{{ __('Нова додаткова ліцензія') }}</x-slot>
        </x-section-navigation>
        <fieldset class="fieldset">
            <form class="form">
                <div class="form-row-3">
                    <div class="form-group">
                        <input type="text" name="license_kind" id="license_kind" class="peer input" value="Додаткова" placeholder=" " required />
                        <label for="license_kind" class="label">Вид ліцензії</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group group relative">
                        <label for="licenseType" class="label">{{ __('Тип ліцензії') }}*</label>
                        <select id="licenseType" name="licenseType" class="input-select peer text-sm leading-snug w-full" required>
                            <option value="" disabled selected hidden>Тип ліцензії</option>
                            <option value="MSP">
                                Ліцензія на провадження господарської діяльності з медичної практики
                            </option>
                            <option value="PHARMACY">
                                Ліцензія на провадження господарської діяльності з виробництва лікарських засобів,
                                оптової та роздрібної торгівлі лікарськими засобами, імпорту лікарських засобів (крім активних фармацевтичних інгредієнтів)
                            </option>
                            <option value="PHARMACY_DRUGS">
                                Ліцензія на право провадження господарської діяльності з розроблення, виробництва, виготовлення, зберігання, перевезення,
                                придбання, реалізації (відпуску) наркотичних засобів, психотропних речовин і прекурсорів
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="floating_order_number" id="floating_order_number" class="peer input" placeholder=" " required />
                        <label for="floating_order_number" class="label">Номер наказу</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <input type="text" name="floating_issued_the_license" id="floating_issued_the_license" class="peer input" placeholder=" " required />
                        <label for="floating_issued_the_license" class="label">Ким видано</label>
                    </div>
                    <div class="form-group">
                        <input type="text" name="floating_licensed_activity" id="floating_licensed_activity" class="peer input" placeholder=" " required />
                        <label for="floating_licensed_activity" class="label">Напрям діяльності, що ліцензовано</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <input type="text" name="floating_license_series_number" id="floating_license_series_number" class="peer input" placeholder=" " required />
                        <label for="floating_license_series_number" class="label">Серія та/або номер ліцензії</label>
                    </div>
                    <div class="form-group relative datepicker-wrapper">
                        <input x-ref="calendar" type="text" onfocus="(this.type='date')" onblur="(this.type='text')" name="floating_date_of_license_issuance" id="floating_date_of_license_issuance" class="peer input pl-10" placeholder=" " required />
                        <label for="floating_date_of_license_issuance" class="wrapped-label">Дата видачі ліцензії</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group datepicker-wrapper relative">
                        <input x-ref="calendar" type="text" onfocus="(this.type='date')" onblur="(this.type='text')" name="floating_date_of_license_start_date" id="floating_date_of_license_start_date" class="peer input pl-10" placeholder=" " required />
                        <label for="floating_date_of_license_start_date" class="wrapped-label">Дата початку дії ліцензії</label>
                    </div>
                    <div class="form-group datepicker-wrapper relative">
                        <input x-ref="calendar" type="text" onfocus="(this.type='date')" onblur="(this.type='text')" name="floating_date_of_license_expiry" id="floating_date_of_license_expiry" class="peer input pl-10" placeholder=" " required />
                        <label for="floating_date_of_license_expiry" class="wrapped-label">Дата завершення дії ліцензії</label>
                    </div>
                </div>
                <div class="form-button-group">
                    <button type="button" class="button-minor">
                        Скасувати
                    </button>
                    <button type="submit" class="button-primary">
                        Додати ліцензію
                    </button>
                </div>
            </form>
        </fieldset>
    </div>
    </div>
</body>