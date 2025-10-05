<x-app-layout>
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-red-50">
    <div class="flex h-screen">
        <!-- Left Sidebar Navigation -->
        <div class="hidden lg:flex lg:flex-shrink-0">
            <div class="flex flex-col w-80 bg-white/80 backdrop-blur-xl border-r border-slate-200/60 shadow-xl h-full">
                <div class="flex flex-col flex-grow pt-8 pb-4 overflow-y-auto">
                    <div class="flex items-center flex-shrink-0 px-6 mb-8">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">Documentation</h1>
                        </div>
                    </div>
                    <nav class="flex-1 px-4 space-y-8">
                        @foreach($navigation as $category)
                            <div class="space-y-4">
                                <div class="px-3 py-2 text-xs font-bold text-slate-500 uppercase tracking-wider bg-slate-100/50 rounded-lg">
                                    {{ $category['title'] }}
                                </div>
                                <div class="space-y-1">
                                    @foreach($category['pages'] as $page)
                                        <a href="{{ $page['url'] }}" 
                                           class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200
                                                  {{ request()->route('category') === $category['name'] && request()->route('page') === $page['name'] 
                                                     ? 'bg-gradient-to-r from-red-500 to-red-600 text-white shadow-lg shadow-red-500/25 transform scale-[1.02]' 
                                                     : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 hover:shadow-md hover:scale-[1.01]' }}">
                                            <div class="w-2 h-2 rounded-full mr-3 {{ request()->route('category') === $category['name'] && request()->route('page') === $page['name'] ? 'bg-white' : 'bg-slate-300 group-hover:bg-slate-400' }}"></div>
                                            <span class="truncate">{{ $page['title'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <div class="flex-1 flex overflow-hidden">
                <!-- Main Documentation Content -->
                <div class="flex-1 overflow-y-auto">
                    <div class="max-w-5xl mx-auto px-6 lg:px-8 py-16">
                        <div class="relative">
                            <!-- Content Background -->
                            <div class="absolute inset-0 bg-white/60 backdrop-blur-sm rounded-3xl shadow-2xl shadow-slate-200/50"></div>
                            
                            <!-- Content Container -->
                            <div class="relative z-10 p-8 lg:p-12">
                                <div class="prose prose-lg prose-slate max-w-none">
                                    {!! $content !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar - Table of Contents -->
                @if(count($toc) > 0)
                <div class="hidden xl:block flex-shrink-0 w-80 h-full overflow-y-auto">
                    <div class="sticky top-0 p-6 h-full">
                        <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-6">
                            <div class="flex items-center space-x-2 mb-6">
                                <div class="w-2 h-2 bg-gradient-to-r from-red-500 to-red-600 rounded-full"></div>
                                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">On this page</h3>
                            </div>
                            <nav class="space-y-3">
                                @foreach($toc as $item)
                                    <a href="#{{ $item['id'] }}" 
                                       class="group flex items-center text-sm text-slate-600 hover:text-red-600 transition-all duration-200 hover:translate-x-1
                                              {{ $item['level'] === 1 ? 'font-semibold text-slate-900' : '' }}
                                              {{ $item['level'] === 2 ? 'ml-3' : '' }}
                                              {{ $item['level'] === 3 ? 'ml-6' : '' }}
                                              {{ $item['level'] === 4 ? 'ml-9' : '' }}
                                              {{ $item['level'] === 5 ? 'ml-12' : '' }}
                                              {{ $item['level'] === 6 ? 'ml-15' : '' }}">
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-300 group-hover:bg-red-500 mr-3 transition-colors duration-200"></div>
                                        <span class="truncate">{{ $item['text'] }}</span>
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Mobile Navigation -->
<div class="lg:hidden" id="mobile-nav" style="display: none;">
    <div class="fixed inset-0 z-40 flex">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="toggleMobileNav()"></div>
        <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white/95 backdrop-blur-xl">
            <div class="absolute top-0 right-0 -mr-12 pt-2">
                <button type="button" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full bg-white/90 backdrop-blur-sm shadow-lg focus:outline-none focus:ring-2 focus:ring-inset focus:ring-red-500" onclick="toggleMobileNav()">
                    <span class="sr-only">Close sidebar</span>
                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 h-0 pt-8 pb-4 overflow-y-auto">
                <div class="flex-shrink-0 flex items-center px-6 mb-8">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-red-600 rounded-lg flex items-center justify-center shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">Documentation</h1>
                    </div>
                </div>
                <nav class="px-4 space-y-8">
                    @foreach($navigation as $category)
                        <div class="space-y-4">
                            <div class="px-3 py-2 text-xs font-bold text-slate-500 uppercase tracking-wider bg-slate-100/50 rounded-lg">
                                {{ $category['title'] }}
                            </div>
                            <div class="space-y-1">
                                @foreach($category['pages'] as $page)
                                    <a href="{{ $page['url'] }}" 
                                       class="group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200
                                              {{ request()->route('category') === $category['name'] && request()->route('page') === $page['name'] 
                                                 ? 'bg-gradient-to-r from-red-500 to-red-600 text-white shadow-lg shadow-red-500/25' 
                                                 : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 hover:shadow-md' }}">
                                        <div class="w-2 h-2 rounded-full mr-3 {{ request()->route('category') === $category['name'] && request()->route('page') === $page['name'] ? 'bg-white' : 'bg-slate-300 group-hover:bg-slate-400' }}"></div>
                                        <span class="truncate">{{ $page['title'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Mobile menu button -->
<div class="lg:hidden fixed top-6 left-6 z-50">
    <button type="button" class="bg-white/90 backdrop-blur-xl shadow-xl p-4 rounded-2xl text-slate-600 hover:text-slate-900 hover:bg-white hover:shadow-2xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500" onclick="toggleMobileNav()">
        <span class="sr-only">Open sidebar</span>
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</div>

<script>
function toggleMobileNav() {
    const mobileNav = document.getElementById('mobile-nav');
    if (mobileNav) {
        mobileNav.style.display = mobileNav.style.display === 'none' ? 'block' : 'none';
    }
}

// Smooth scrolling for table of contents links with active state tracking
document.addEventListener('DOMContentLoaded', function() {
    const tocLinks = document.querySelectorAll('nav a[href^="#"]');
    const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
    const scrollContainer = document.querySelector('.flex-1.overflow-y-auto');
    
    // Function to update active TOC link
    function updateActiveTOCLink() {
        const scrollTop = scrollContainer.scrollTop;
        const containerHeight = scrollContainer.clientHeight;
        const scrollMiddle = scrollTop + containerHeight / 2;
        
        let activeHeading = null;
        let minDistance = Infinity;
        
        headings.forEach(heading => {
            const headingTop = heading.offsetTop;
            const distance = Math.abs(headingTop - scrollMiddle);
            
            if (headingTop <= scrollMiddle && distance < minDistance) {
                minDistance = distance;
                activeHeading = heading;
            }
        });
        
        // Remove active class from all links
        tocLinks.forEach(link => {
            // Remove active state
            link.classList.remove('toc-active');
            link.style.background = '';
            link.style.color = '';
            link.style.transform = '';
            link.style.boxShadow = '';
            
            // Reset dot color
            const dot = link.querySelector('div');
            if (dot) {
                dot.style.backgroundColor = '';
            }
        });
        
        // Add active class to current heading's link
        if (activeHeading) {
            const activeLink = document.querySelector(`nav a[href="#${activeHeading.id}"]`);
            if (activeLink) {
                // Add active state with inline styles to override any existing classes
                activeLink.classList.add('toc-active');
                activeLink.style.background = 'linear-gradient(to right, #ef4444, #dc2626)';
                activeLink.style.color = 'white';
                activeLink.style.transform = 'scale(1.02)';
                activeLink.style.boxShadow = '0 10px 15px -3px rgba(239, 68, 68, 0.25)';
                
                // Update dot color
                const dot = activeLink.querySelector('div');
                if (dot) {
                    dot.style.backgroundColor = 'white';
                }
            }
        }
    }
    
    // Handle TOC link clicks
    tocLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // If we found a permalink, get its parent heading
                let headingElement = targetElement;
                if (targetElement.classList.contains('heading-permalink')) {
                    headingElement = targetElement.parentElement;
                }
                
                if (scrollContainer) {
                    // Calculate relative position within the scroll container
                    const containerRect = scrollContainer.getBoundingClientRect();
                    const elementRect = headingElement.getBoundingClientRect();
                    const scrollTop = scrollContainer.scrollTop + (elementRect.top - containerRect.top) - 100;
                    
                    scrollContainer.scrollTo({
                        top: scrollTop,
                        behavior: 'smooth'
                    });
                } else {
                    headingElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Update active state on scroll
    if (scrollContainer) {
        scrollContainer.addEventListener('scroll', updateActiveTOCLink);
        // Initial update
        updateActiveTOCLink();
    }
});
</script>

<style>
/* Hide table of contents */
.table-of-contents {
    display: none !important;
}

/* Hide heading permalinks to avoid the # appearing after titles */
.heading-permalink {
    display: none !important;
}

/* TOC active state styling */
.toc-active {
    background: linear-gradient(to right, #ef4444, #dc2626) !important;
    color: white !important;
    transform: scale(1.02) !important;
    box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.25) !important;
    transition: all 0.2s ease !important;
}

/* Ensure TOC links have proper transitions */
nav a[href^="#"] {
    transition: all 0.2s ease !important;
}

/* Table styling */
.prose table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 2rem 0 !important;
    border-radius: 0.75rem !important;
    overflow: hidden !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
}

.prose thead {
    background: linear-gradient(135deg, #1e293b, #334155) !important;
}

.prose th {
    padding: 1rem !important;
    text-align: left !important;
    font-weight: 600 !important;
    color: white !important;
    font-size: 0.875rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    border: none !important;
}

.prose tbody tr {
    border-bottom: 1px solid #e2e8f0 !important;
    transition: background-color 0.2s ease !important;
}

.prose tbody tr:hover {
    background-color: #f8fafc !important;
}

.prose tbody tr:last-child {
    border-bottom: none !important;
}

.prose td {
    padding: 1rem !important;
    color: #374151 !important;
    font-size: 0.875rem !important;
    border: none !important;
}

.prose tbody tr:nth-child(even) {
    background-color: #f9fafb !important;
}

/* Ensure prose styling works correctly */
.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
    font-weight: bold !important;
    margin-top: 2rem !important;
    margin-bottom: 1rem !important;
}

.prose h1 {
    margin-top: 0 !important;
}

.prose p {
    margin-top: 1rem !important;
    margin-bottom: 1.25rem !important;
}

.prose ul {
    list-style-type: disc !important;
    margin-left: 1.5rem !important;
    margin-bottom: 1.25rem !important;
}

.prose ol {
    list-style-type: decimal !important;
    margin-left: 1.5rem !important;
    margin-bottom: 1.25rem !important;
}

.prose li {
    margin-bottom: 0.5rem !important;
    display: list-item !important;
}
</style>
</x-app-layout>
