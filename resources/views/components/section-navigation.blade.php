@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'navigation-container']) }}>
    <div class="navigation-content">
        @if($title)
            <div class="form-header mb-4">
                <div class="form-header-title">
                    <nav class="navigation-breadcrumb" aria-label="Breadcrumb">
                        <ol class="navigation-breadcrumb-list">
                            <li class="navigation-breadcrumb-item">
                                <a href="#" class="navigation-breadcrumb-link">
                                    <svg class="w-5 h-5 mr-2.5" fill="currentColor" viewBox="0 0 20 20"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                    </svg>
                                    Головна
                                </a>
                            </li>
                            <li class="navigation-breadcrumb-item">
                                <div class="flex items-center">
                                    <svg class="navigation-breadcrumb-separator" fill="currentColor" viewBox="0 0 20 20"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                              clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="navigation-breadcrumb-current" aria-current="page">{{ $title }}</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="navigation-title">{{ $title }}</h1>

                    @if($description)
                        <p class="navigation-description">
                            <span>{{ $description }}</span>
                        </p>
                    @endif
                </div>
                <div class="form-header-actions">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </div>
</div>
