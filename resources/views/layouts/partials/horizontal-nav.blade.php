<!-- Horizontal Menu Start -->
<header class="topnav">
    <nav class="navbar navbar-expand-lg">
        <nav class="page-container">
            <div class="collapse navbar-collapse" id="topnav-menu-content">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown hover-dropdown">
                        <a class="nav-link dropdown-toggle drop-arrow-none" href="/">
                            <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                            <span class="menu-text"> Dashboard </span>
                        </a>
                    </li>

                    <li class="nav-item dropdown hover-dropdown">
                        <a class="nav-link dropdown-toggle drop-arrow-none" href="#" id="topnav-apps" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="menu-icon"><i class="ti ti-apps"></i></span>
                            <span class="menu-text">Apps</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-apps">
                            <a href="{{ route('second', ['apps', 'calendar'])}}" class="dropdown-item">Calendar</a>
                            <a href="{{ route('second', ['apps', 'chat'])}}" class="dropdown-item">Chat</a>
                            <a href="{{ route('second', ['apps', 'email'])}}" class="dropdown-item">Email</a>
                            <a href="{{ route('second', ['apps', 'file-manager'])}}" class="dropdown-item">File Manager</a>
                            <a href="{{ route('second', ['apps', 'projects'])}}" class="dropdown-item">Projects</a>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-user"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    User
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-user">
                                    <a href="{{ route('second', ['apps', 'user-contacts'])}}" class="dropdown-item">Contacts</a>
                                    <a href="{{ route('second', ['apps', 'user-profile'])}}" class="dropdown-item">Profile</a>
                                </div>
                            </div>

                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-tasks"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Tasks
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-tasks">
                                    <a href="{{ route('second', ['apps', 'kanban'])}}" class="dropdown-item">Kanban</a>
                                    <a href="{{ route('second', ['apps', 'task-detail'])}}" class="dropdown-item">View Details</a>
                                </div>
                            </div>

                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-invoices"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Invoice
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-invoices">
                                    <a href="{{ route('second', ['apps', 'invoices'])}}" class="dropdown-item">Invoices</a>
                                    <a href="{{ route('second', ['apps', 'invoice-details'])}}" class="dropdown-item">View Invoice</a>
                                    <a href="{{ route('second', ['apps', 'invoice-create'])}}" class="dropdown-item">Create Invoice</a>
                                </div>
                            </div>

                        </div>
                    </li>

                    <li class="nav-item dropdown hover-dropdown">
                        <a class="nav-link dropdown-toggle drop-arrow-none" href="#" id="topnav-pages" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="menu-icon"><i class="ti ti-file-description"></i></span>
                            <span class="menu-text">Pages</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-pages">
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-auth"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Authentication <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-auth">
                                    <a href="{{ route('second', ['auth', 'login'])}}" class="dropdown-item">Login</a>
                                    <a href="{{ route('second', ['auth', 'register'])}}" class="dropdown-item">Register</a>
                                    <a href="{{ route('second', ['auth', 'loyout'])}}" class="dropdown-item">Logout</a>
                                    <a href="{{ route('second', ['auth', 'recoverpw'])}}" class="dropdown-item">Recover Password</a>
                                    <a href="{{ route('second', ['auth', 'createpw'])}}" class="dropdown-item">Create Password</a>
                                    <a href="{{ route('second', ['auth', 'lock-screen'])}}" class="dropdown-item">Lock Screen</a>
                                    <a href="{{ route('second', ['auth', 'confirm-mail'])}}" class="dropdown-item">Confirm Mail</a>
                                    <a href="{{ route('second', ['auth', 'login-pin'])}}" class="dropdown-item">Login with PIN</a>
                                </div>
                            </div>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-error"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Error <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-error">
                                    <a href="{{ route('second', ['error', '401'])}}" class="dropdown-item">401 Unauthorized</a>
                                    <a href="{{ route('second', ['error', '400'])}}" class="dropdown-item">400 Bad Request</a>
                                    <a href="{{ route('second', ['error', '403'])}}" class="dropdown-item">403 Forbidden</a>
                                    <a href="{{ route('second', ['error', '404'])}}" class="dropdown-item">404 Not Found</a>
                                    <a href="{{ route('second', ['error', '500'])}}" class="dropdown-item">500 Internal Server</a>
                                    <a href="{{ route('second', ['error', 'service-unavailable'])}}" class="dropdown-item">Service
                                        Unavailable</a>
                                    <a href="{{ route ('second' , ['error','404-alt']) }}" class="dropdown-item">Error 404 Alt</a>
                                </div>
                            </div>
                            <a href="{{ route('second', ['pages', 'starter'])}}" class="dropdown-item">Starter Page</a>
                            <a href="{{ route('second', ['pages', 'faq'])}}" class="dropdown-item">FAQ</a>
                            <a href="{{ route('second', ['pages', 'pricing'])}}" class="dropdown-item">Pricing</a>
                            <a href="{{ route ('second' , ['pages','maintenance']) }}" class="dropdown-item">Maintenance</a>
                            <a href="{{ route ('second' , ['pages','timeline']) }}"class="dropdown-item">Timeline</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown hover-dropdown">
                        <a class="nav-link dropdown-toggle drop-arrow-none" href="#" id="topnav-components"
                            role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="menu-icon"><i class="ti ti-components"></i></span>
                            <span class="menu-text">Components</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-components">
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-ui-kit"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Base UI 1
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-ui-kit">
                                    <a href="{{ route('second', ['ui', 'accordions'])}}"  class="dropdown-item">Accordions</a>
                                    <a href="{{ route('second', ['ui', 'alerts'])}}"  class="dropdown-item">Alerts</a>
                                    <a href="{{ route('second', ['ui', 'avatars'])}}"  class="dropdown-item">Avatars</a>
                                    <a href="{{ route('second', ['ui', 'badges'])}}"  class="dropdown-item">Badges</a>
                                    <a href="{{ route('second', ['ui', 'breadcrumb'])}}"  class="dropdown-item">Breadcrumb</a>
                                    <a href="{{ route('second', ['ui', 'buttons'])}}"  class="dropdown-item">Buttons</a>
                                    <a href="{{ route('second', ['ui', 'cards'])}}"  class="dropdown-item">Cards</a>
                                    <a href="{{ route('second', ['ui', 'carousel'])}}"  class="dropdown-item">Carousel</a>
                                    <a href="{{ route('second', ['ui', 'dropdowns'])}}"  class="dropdown-item">Dropdowns</a>
                                    <a href="{{ route('second', ['ui', 'embed-video'])}}"  class="dropdown-item">Embed Video</a>
                                    <a href="{{ route('second', ['ui', 'grid'])}}"  class="dropdown-item">Grid</a>
                                    <a href="{{ route('second', ['ui', 'list-group'])}}"  class="dropdown-item">List Group</a>
                                </div>
                            </div>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-ui-kit2"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Base UI 2
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-ui-kit2">
                                    <a href="{{ route('second', ['ui', 'modals'])}}"  class="dropdown-item">Modals</a>
                                    <a href="{{ route('second', ['ui', 'notifications'])}}"  class="dropdown-item">Notifications</a>
                                    <a href="{{ route('second', ['ui', 'offcanvas'])}}"  class="dropdown-item">Offcanvas</a>
                                    <a href="{{ route('second', ['ui', 'placeholders'])}}"  class="dropdown-item">Placeholders</a>
                                    <a href="{{ route('second', ['ui', 'pagination'])}}"  class="dropdown-item">Pagination</a>
                                    <a href="{{ route('second', ['ui', 'popovers'])}}"  class="dropdown-item">Popovers</a>
                                    <a href="{{ route('second', ['ui', 'progress'])}}"  class="dropdown-item">Progress</a>
                                    <a href="{{ route('second', ['ui', 'spinners'])}}"  class="dropdown-item">Spinners</a>
                                    <a href="{{ route('second', ['ui', 'tabs'])}}"  class="dropdown-item">Tabs</a>
                                    <a href="{{ route('second', ['ui', 'tooltips'])}}"  class="dropdown-item">Tooltips</a>
                                    <a href="{{ route('second', ['ui', 'links'])}}"  class="dropdown-item">Links</a>
                                    <a href="{{ route('second', ['ui', 'typography'])}}"  class="dropdown-item">Typography</a>
                                    <a href="{{ route('second', ['ui', 'utilities'])}}"  class="dropdown-item">Utilities</a>
                                </div>
                            </div>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#"
                                    id="topnav-extended-ui" role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    Extended UI
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-extended-ui">
                                    <a href="{{ route ('second' , ['extended-ui','dragula']) }}" class="dropdown-item">Dragula</a>
                                    <a href="{{ route ('second' , ['extended-ui','sweetalerts']) }}" class="dropdown-item">Sweet Alerts</a>
                                    <a href="{{ route ('second' , ['extended-ui','ratings']) }}" class="dropdown-item">Ratings</a>
                                    <a href="{{ route ('second' , ['extended-ui','scrollbar']) }}" class="dropdown-item">Scrollbar</a>
                                </div>
                            </div>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-forms"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Forms
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-forms">
                                    <a href="{{ route ('second' , ['form','elements']) }}" class="dropdown-item">Basic Elements</a>
                                    <a href="{{ route ('second' , ['form','inputmask']) }}" class="dropdown-item">Inputmask</a>
                                    <a href="{{ route ('second' , ['form','picker']) }}" class="dropdown-item">Picker</a>
                                    <a href="{{ route ('second' , ['form','select']) }}" class="dropdown-item">Select</a>
                                    <a href="{{ route ('second' , ['form','range-slider']) }}" class="dropdown-item">Range Slider</a>
                                    <a href="{{ route ('second' , ['form','validation']) }}" class="dropdown-item">Validation</a>
                                    <a href="{{ route ('second' , ['form','wizard']) }}" class="dropdown-item">Wizard</a>
                                    <a href="{{ route ('second' , ['form','fileuploads']) }}" class="dropdown-item">File Uploads</a>
                                    <a href="{{ route ('second' , ['form','editors']) }}" class="dropdown-item">Editors</a>
                                    <a href="{{ route ('second' , ['form','layouts']) }}" class="dropdown-item">Layouts</a>
                                </div>
                            </div>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-charts"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Charts
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-lg" aria-labelledby="topnav-charts">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <a href="{{ route ('second' , ['charts','area']) }}" class="dropdown-item">Area</a>
                                            <a href="{{ route ('second' , ['charts','bar']) }}" class="dropdown-item">Bar</a>
                                            <a href="{{ route ('second' , ['charts','bubble']) }}" class="dropdown-item">Bubble</a>
                                            <a href="{{ route ('second' , ['charts','candlestick']) }}" class="dropdown-item">Candlestick</a>
                                            <a href="{{ route ('second' , ['charts','column']) }}" class="dropdown-item">Column</a>
                                            <a href="{{ route ('second' , ['charts','heatmap']) }}" class="dropdown-item">Heatmap</a>
                                            <a href="{{ route ('second' , ['charts','line']) }}" class="dropdown-item">Line</a>
                                            <a href="{{ route ('second' , ['charts','mixed']) }}" class="dropdown-item">Mixed</a>
                                            <a href="{{ route ('second' , ['charts','timeline']) }}" class="dropdown-item">Timeline</a>
                                            <a href="{{ route ('second' , ['charts','boxplot']) }}" class="dropdown-item">Boxplot</a>
                                        </div>

                                        <div class="col-md-6">
                                            <a href="{{ route ('second' , ['charts','treemap']) }}" class="dropdown-item">Treemap</a>
                                            <a href="{{ route ('second' , ['charts','pie']) }}" class="dropdown-item">Pie</a>
                                            <a href="{{ route ('second' , ['charts','radar']) }}" class="dropdown-item">Radar</a>
                                            <a href="{{ route ('second' , ['charts','radialbar']) }}" class="dropdown-item">RadialBar</a>
                                            <a href="{{ route ('second' , ['charts','scatter']) }}" class="dropdown-item">Scatter</a>
                                            <a href="{{ route ('second' , ['charts','polar-area']) }}" class="dropdown-item">Polar Area</a>
                                            <a href="{{ route ('second' , ['charts','sparklines']) }}" class="dropdown-item">Sparklines</a>
                                            <a href="{{ route ('second' , ['charts','slope']) }}" class="dropdown-item">Slope</a>
                                            <a href="{{ route ('second' , ['charts','funnel']) }}" class="dropdown-item">Funnel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-tables"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Tables
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-tables">
                                    <a href="{{ route ('second' , ['tables','basic']) }}" class="dropdown-item">Basic Tables</a>
                                    <a href="{{ route ('second' , ['tables','gridjs']) }}" class="dropdown-item">Gridjs Tables</a>
                                    <a href="{{ route ('second' , ['tables','datatable']) }}" class="dropdown-item">Datatable Tables</a>
                                </div>
                            </div>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-icons"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Icons
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-icons">
                                    <a href="{{ route ('second' , ['icons','tabler']) }}" class="dropdown-item">Tabler Icons</a>
                                    <a href="{{ route ('second' , ['icons','remix']) }}" class="dropdown-item">Remix Icons</a>
                                    <a href="{{ route ('second' , ['icons','solar']) }}" class="dropdown-item">Solar Design</a>
                                </div>
                            </div>
                            <div class="dropdown hover-dropdown">
                                <a class="dropdown-item dropdown-toggle drop-arrow-none" href="#" id="topnav-maps"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Maps
                                    <div class="menu-arrow"></div>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="topnav-maps">
                                    <a href="{{ route ('second' , ['maps','google']) }}" class="dropdown-item">Google Maps</a>
                                    <a href="{{ route ('second' , ['maps','vector']) }}" class="dropdown-item">Vector Maps</a>
                                    <a href="{{ route ('second' , ['maps','leaflet']) }}" class="dropdown-item">Leaflet Maps</a>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item dropdown hover-dropdown">
                        <a class="nav-link dropdown-toggle drop-arrow-none" href="#" id="topnav-layouts" role="button"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="menu-icon"><i class="ti ti-layout"></i></span>
                            <span class="menu-text">Layouts</span>
                            <div class="menu-arrow"></div>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="topnav-layouts">
                            <a href="{{ route('any', ['index'])}}" class="dropdown-item" target="_blank">Vertical</a>
                            <a href="{{ route('second', ['layouts-eg', 'horizontal'])}}" class="dropdown-item" target="_blank">Horizontal</a>
                            <a href="{{ route('second', ['layouts-eg', 'detached'])}}"  class="dropdown-item" target="_blank">Detached</a>
                            <a href="{{ route('second', ['layouts-eg', 'full'])}}" class="dropdown-item" target="_blank">Full</a>
                            <a href="{{ route('second', ['layouts-eg', 'fullscreen'])}}" class="dropdown-item" target="_blank">Fullscreen</a>
                            <a href="{{ route('second', ['layouts-eg', 'hover'])}}" class="dropdown-item" target="_blank">Hover Menu</a>
                            <a href="{{ route('second', ['layouts-eg', 'compact'])}}" class="dropdown-item" target="_blank">Compact Menu</a>
                            <a href="{{ route('second', ['layouts-eg', 'icon-view'])}}"  class="dropdown-item" target="_blank">Icon View</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </nav>
</header>
<!-- Horizontal Menu End -->
