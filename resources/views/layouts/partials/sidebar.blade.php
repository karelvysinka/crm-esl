<!-- Sidenav Menu Start -->
<div class="sidenav-menu">

    <!-- Brand Logo -->
    <a href="{{ route('any', ['index'])}}" class="logo">
        <span class="logo-light">
            <span class="logo-lg"><img src="/images/logo.png" alt="logo"></span>
            <span class="logo-sm"><img src="/images/logo-sm.png" alt="small logo"></span>
        </span>

        <span class="logo-dark">
            <span class="logo-lg"><img src="{{ asset("images/logo-dark.png") }}" alt="dark logo"></span>
            <span class="logo-sm"><img src="/images/logo-sm.png" alt="small logo"></span>
        </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-sm-hover">
        <i class="ri-circle-line align-middle"></i>
    </button>

    <!-- Sidebar Menu Toggle Button -->
    <button class="sidenav-toggle-button">
        <i class="ri-menu-5-line fs-20"></i>
    </button>

    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-fullsidebar">
        <i class="ti ti-x align-middle"></i>
    </button>

    <div data-simplebar>

        <!-- User -->
        <div class="sidenav-user">
            <div class="dropdown-center text-center">
                <a class="topbar-link dropdown-toggle text-reset drop-arrow-none px-2" data-bs-toggle="dropdown"
                    type="button" aria-haspopup="false" aria-expanded="false">
                    <img src="/images/users/avatar-1.jpg" width="46" class="rounded-circle" alt="user-image">
                    <span class="d-flex gap-1 sidenav-user-name my-2">
                        <span>
                            <span class="mb-0 fw-semibold lh-base fs-15">Nowak Helme</span>
                            <p class="my-0 fs-13 text-muted">Admin Head</p>
                        </span>

                        <i class="ri-arrow-down-s-line d-block sidenav-user-arrow align-middle"></i>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <!-- item-->
                    <div class="dropdown-header noti-title">
                        <h6 class="text-overflow m-0">Welcome !</h6>
                    </div>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="ri-account-circle-line me-1 fs-16 align-middle"></i>
                        <span class="align-middle">My Account</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="ri-wallet-3-line me-1 fs-16 align-middle"></i>
                        <span class="align-middle">Wallet : <span class="fw-semibold">$89.25k</span></span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="ri-settings-2-line me-1 fs-16 align-middle"></i>
                        <span class="align-middle">Settings</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="ri-question-line me-1 fs-16 align-middle"></i>
                        <span class="align-middle">Support</span>
                    </a>

                    <div class="dropdown-divider"></div>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="ri-lock-line me-1 fs-16 align-middle"></i>
                        <span class="align-middle">Lock Screen</span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item active fw-semibold text-danger">
                        <i class="ri-logout-box-line me-1 fs-16 align-middle"></i>
                        <span class="align-middle">Sign Out</span>
                    </a>
                </div>
            </div>
        </div>

        <!--- Sidenav Menu -->
        <ul class="side-nav">
            <!-- CRM Section -->
            <li class="side-nav-title">CRM</li>
            
            <li class="side-nav-item">
                <a href="{{ route('crm.dashboard') }}" class="side-nav-link">
                    <span class="menu-icon"><i class="mdi mdi-view-dashboard"></i></span>
                    <span class="menu-text"> CRM Dashboard </span>
                </a>
            </li>

            <!-- Marketing Section -->
            <li class="side-nav-title">Marketing</li>

            <li class="side-nav-item">
                <a href="{{ route('marketing.dashboard') }}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-bullhorn"></i></span>
                    <span class="menu-text"> Marketing Dashboard </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMarketingStrategie" aria-expanded="false" aria-controls="sidebarMarketingStrategie" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-target"></i></span>
                    <span class="menu-text"> Strategie </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMarketingStrategie">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.calendar') }}" class="side-nav-link"><span class="menu-text">Marketingový kalendář</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.budget') }}" class="side-nav-link"><span class="menu-text">Správa Budgetu</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.personas') }}" class="side-nav-link"><span class="menu-text">Cílové Persony</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.swot') }}" class="side-nav-link"><span class="menu-text">SWOT & konkurence</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.trends') }}" class="side-nav-link"><span class="menu-text">Trendy & AI</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMarketingExekuce" aria-expanded="false" aria-controls="sidebarMarketingExekuce" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-player-play"></i></span>
                    <span class="menu-text"> Exekuce </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMarketingExekuce">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.campaigns') }}" class="side-nav-link"><span class="menu-text">Kampaně</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.automation') }}" class="side-nav-link"><span class="menu-text">Automatizace</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.content') }}" class="side-nav-link"><span class="menu-text">Knihovna Obsahu</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.ads') }}" class="side-nav-link"><span class="menu-text">Reklamy (PPC & Social)</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.email') }}" class="side-nav-link"><span class="menu-text">E-mail marketing</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.influencers') }}" class="side-nav-link"><span class="menu-text">Influenceři & partneři</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.landing') }}" class="side-nav-link"><span class="menu-text">Landing Pages</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMarketingCileni" aria-expanded="false" aria-controls="sidebarMarketingCileni" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-users"></i></span>
                    <span class="menu-text"> Kontakty & Cílení </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMarketingCileni">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.target.contacts') }}" class="side-nav-link"><span class="menu-text">Databáze kontaktů</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.target.segments') }}" class="side-nav-link"><span class="menu-text">Segmentace</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.target.nurturing') }}" class="side-nav-link"><span class="menu-text">Lead Nurturing</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMarketingAnalytika" aria-expanded="false" aria-controls="sidebarMarketingAnalytika" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="menu-text"> Analytika & Reporty </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMarketingAnalytika">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.seo') }}" class="side-nav-link"><span class="menu-text">SEO Přehled</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.attribution') }}" class="side-nav-link"><span class="menu-text">Atribuce konverzí</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.cross') }}" class="side-nav-link"><span class="menu-text">Cross-channel</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.ab') }}" class="side-nav-link"><span class="menu-text">A/B testování</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.sentiment') }}" class="side-nav-link"><span class="menu-text">AI Sentiment</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMarketingNastaveni" aria-expanded="false" aria-controls="sidebarMarketingNastaveni" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-settings"></i></span>
                    <span class="menu-text"> Nastavení </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMarketingNastaveni">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.integrations') }}" class="side-nav-link"><span class="menu-text">Integrace & API</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.scoring') }}" class="side-nav-link"><span class="menu-text">Lead Scoring</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.roles') }}" class="side-nav-link"><span class="menu-text">Role & práva</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.ai') }}" class="side-nav-link"><span class="menu-text">AI šablony</span></a></li>
                    </ul>
                </div>
            </li>

            <!-- System Settings Section -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarSystem" aria-expanded="false" aria-controls="sidebarSystem" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-settings"></i></span>
                    <span class="menu-text"> Systém </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarSystem">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('system.chat.index') }}" class="side-nav-link"><span class="menu-text">Chat</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('system.qdrant.index') }}" class="side-nav-link"><span class="menu-text">Qdrant</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('system.tools.index') }}" class="side-nav-link"><span class="menu-text">Nástroje</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('system.tools.index') }}#playwright" class="side-nav-link"><span class="menu-text">Nástroje – Playwright</span></a></li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarCrm" aria-expanded="false"
                    aria-controls="sidebarCrm" class="side-nav-link">
                    <span class="menu-icon"><i class="mdi mdi-briefcase-variant"></i></span>
                    <span class="menu-text"> CRM Moduly</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarCrm">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('companies.index') }}" class="side-nav-link">
                                <span class="menu-text">Společnosti</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('contacts.index') }}" class="side-nav-link">
                                <span class="menu-text">Kontakty</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('leads.index') }}" class="side-nav-link">
                                <span class="menu-text">Leady</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('opportunities.index') }}" class="side-nav-link">
                                <span class="menu-text">Příležitosti</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('tasks.index') }}" class="side-nav-link">
                                <span class="menu-text">Úkoly</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('deals.index') }}" class="side-nav-link">
                                <span class="menu-text">Obchody</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('products.index') }}" class="side-nav-link">
                                <span class="menu-text">Produkty</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Adminto Default Section -->
            <li class="side-nav-title">Adminto Template</li>
            
            <li class="side-nav-item">
                <a href="{{ route('any', ['index'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                    <span class="menu-text"> Dashboard </span>
                    <span class="badge bg-danger rounded-pill">9+</span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'chat'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-message"></i></span>
                    <span class="menu-text"> Chat </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'calendar'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-calendar"></i></span>
                    <span class="menu-text"> Calendar </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarContacts" aria-expanded="false"
                    aria-controls="sidebarContacts" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-user-square-rounded"></i></span>
                    <span class="menu-text"> Users</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarContacts">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'user-contacts'])}}" class="side-nav-link">
                                <span class="menu-text">Contacts</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'user-profile'])}}" class="side-nav-link">
                                <span class="menu-text">Profile</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'email'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-mailbox"></i></span>
                    <span class="menu-text"> Email </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'file-manager'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-folders"></i></span>
                    <span class="menu-text"> File Manager </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'projects'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-briefcase"></i></span>
                    <span class="menu-text"> Projects </span>
                </a>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarTasks" aria-expanded="false" aria-controls="sidebarTasks"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-layout-kanban"></i></span>
                    <span class="menu-text"> Tasks</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarTasks">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'kanban'])}}" class="side-nav-link">
                                <span class="menu-text">Kanban</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'task-details'])}}" class="side-nav-link">
                                <span class="menu-text">View Details</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarInvoice" aria-expanded="false" aria-controls="sidebarInvoice"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-invoice"></i></span>
                    <span class="menu-text"> Invoice</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarInvoice">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'invoices'])}}" class="side-nav-link">
                                <span class="menu-text">Invoices</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'invoice-details'])}}" class="side-nav-link">
                                <span class="menu-text">View Invoice</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'invoice-create'])}}" class="side-nav-link">
                                <span class="menu-text">Create Invoice</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title mt-2">
                Custom
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPages" aria-expanded="false" aria-controls="sidebarPages"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-package"></i></span>
                    <span class="menu-text"> Pages </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPages">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['pages', 'starter'])}}" class="side-nav-link">
                                <span class="menu-text">Starter Page</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['pages', 'pricing'])}}" class="side-nav-link">
                                <span class="menu-text">Pricing</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['pages', 'faq'])}}" class="side-nav-link">
                                <span class="menu-text">FAQ</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['pages', 'maintenance'])}}" class="side-nav-link">
                                <span class="menu-text">Maintenance</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['pages', 'timeline'])}}" class="side-nav-link">
                                <span class="menu-text">Timeline</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['pages', 'coming-soon'])}}" class="side-nav-link">
                                <span class="menu-text">Coming Soon</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesAuth" aria-expanded="false"
                    aria-controls="sidebarPagesAuth" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-user-shield"></i></span>
                    <span class="menu-text"> Authentication </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesAuth">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route ('second' , ['auth','login']) }}" class="side-nav-link">
                                <span class="menu-text">Login</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['auth', 'register'])}}" class="side-nav-link">
                                <span class="menu-text">Register</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['auth', 'logout'])}}" class="side-nav-link">
                                <span class="menu-text">Logout</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['auth', 'recoverpw'])}}" class="side-nav-link">
                                <span class="menu-text">Recover Password</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['auth', 'createpw'])}}" class="side-nav-link">
                                <span class="menu-text">Create Password</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['auth', 'lock-screen'])}}" class="side-nav-link">
                                <span class="menu-text">Lock Screen</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['auth', 'confirm-mail'])}}" class="side-nav-link">
                                <span class="menu-text">Confirm Mail</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['auth', 'login-pin'])}}" class="side-nav-link">
                                <span class="menu-text">Login with PIN</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesError" aria-expanded="false"
                    aria-controls="sidebarPagesError" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-exclamation-circle"></i></span>
                    <span class="menu-text"> Error Pages </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesError">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['error', '401'])}}" class="side-nav-link">
                                <span class="menu-text">401 Unauthorized</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['error', '400'])}}" class="side-nav-link">
                                <span class="menu-text">400 Bad Request</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['error', '403'])}}" class="side-nav-link">
                                <span class="menu-text">403 Forbidden</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['error', '404'])}}" class="side-nav-link">
                                <span class="menu-text">404 Not Found</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['error', '500'])}}" class="side-nav-link">
                                <span class="menu-text">500 Internal Server</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['error', 'service-unavailable'])}}" class="side-nav-link">
                                <span class="menu-text">Service Unavailable</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['error', '404-alt'])}}" class="side-nav-link">
                                <span class="menu-text">Error 404 Alt</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('second', ['widgets', 'index'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text"> Widgets </span>
                </a>
            </li>

            <li class="side-nav-title mt-2">Components</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarBaseUI" aria-expanded="false" aria-controls="sidebarBaseUI"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-aperture"></i></span>
                    <span class="menu-text"> Base UI </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarBaseUI">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'accordions'])}}"  class="side-nav-link">
                                <span class="menu-text">Accordions</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'alerts'])}}"  class="side-nav-link">
                                <span class="menu-text">Alerts</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'avatars'])}}"  class="side-nav-link">
                                <span class="menu-text">Avatars</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'badges'])}}"  class="side-nav-link">
                                <span class="menu-text">Badges</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'breadcrumb'])}}"  class="side-nav-link">
                                <span class="menu-text">Breadcrumb</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'buttons'])}}"  class="side-nav-link">
                                <span class="menu-text">Buttons</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'cards'])}}"  class="side-nav-link">
                                <span class="menu-text">Cards</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'carousel'])}}"  class="side-nav-link">
                                <span class="menu-text">Carousel</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'collapse'])}}"  class="side-nav-link">
                                <span class="menu-text">Collapse</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'dropdowns'])}}"  class="side-nav-link">
                                <span class="menu-text">Dropdowns</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'ratios'])}}"  class="side-nav-link">
                                <span class="menu-text">Ratios</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'grid'])}}"  class="side-nav-link">
                                <span class="menu-text">Grid</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'links'])}}"  class="side-nav-link">
                                <span class="menu-text">Links</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'list-group'])}}"  class="side-nav-link">
                                <span class="menu-text">List Group</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'modals'])}}"  class="side-nav-link">
                                <span class="menu-text">Modals</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'notifications'])}}"  class="side-nav-link">
                                <span class="menu-text">Notifications</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'offcanvas'])}}"  class="side-nav-link">
                                <span class="menu-text">Offcanvas</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'placeholders'])}}"  class="side-nav-link">
                                <span class="menu-text">Placeholders</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'pagination'])}}"  class="side-nav-link">
                                <span class="menu-text">Pagination</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'popovers'])}}"  class="side-nav-link">
                                <span class="menu-text">Popovers</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'progress'])}}"  class="side-nav-link">
                                <span class="menu-text">Progress</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'scrollspy'])}}"  class="side-nav-link">
                                <span class="menu-text">Scrollspy</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'spinners'])}}"  class="side-nav-link">
                                <span class="menu-text">Spinners</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'tabs'])}}"  class="side-nav-link">
                                <span class="menu-text">Tabs</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'tooltips'])}}"  class="side-nav-link">
                                <span class="menu-text">Tooltips</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'typography'])}}"  class="side-nav-link">
                                <span class="menu-text">Typography</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['ui', 'utilities'])}}"  class="side-nav-link">
                                <span class="menu-text">Utilities</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarExtendedUI" aria-expanded="false"
                    aria-controls="sidebarExtendedUI" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-macro"></i></span>
                    <span class="menu-text"> Extended UI </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarExtendedUI">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['extended-ui', 'dragula'])}}" class="side-nav-link">
                                <span class="menu-text">Dragula</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route ('second' , ['extended-ui','sweetalerts']) }}" class="side-nav-link">
                                <span class="menu-text">Sweet Alerts</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route ('second' , ['extended-ui','ratings']) }}" class="side-nav-link">
                                <span class="menu-text">Ratings</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route ('second' , ['extended-ui','scrollbar']) }}" class="side-nav-link">
                                <span class="menu-text">Scrollbar</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarIcons" aria-expanded="false" aria-controls="sidebarIcons"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-icons"></i></span>
                    <span class="menu-text"> Icons </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarIcons">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['icons', 'remix'])}}" class="side-nav-link">
                                <span class="menu-text">Remix</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['icons', 'tabler'])}}" class="side-nav-link">
                                <span class="menu-text">Tabler</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['icons', 'solar'])}}" class="side-nav-link">
                                <span class="menu-text">Solar</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarCharts" aria-expanded="false" aria-controls="sidebarCharts"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-chart-infographic"></i></span>
                    <span class="menu-text"> Charts </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarCharts">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'area'])}}" class="side-nav-link">
                                <span class="menu-text">Area</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'bar'])}}" class="side-nav-link">
                                <span class="menu-text">Bar</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'bubble'])}}" class="side-nav-link">
                                <span class="menu-text">Bubble</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'candlestick'])}}" class="side-nav-link">
                                <span class="menu-text">Candlestick</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'column'])}}" class="side-nav-link">
                                <span class="menu-text">Column</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'heatmap'])}}" class="side-nav-link">
                                <span class="menu-text">Heatmap</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'line'])}}" class="side-nav-link">
                                <span class="menu-text">Line</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'mixed'])}}" class="side-nav-link">
                                <span class="menu-text">Mixed</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'timeline'])}}" class="side-nav-link">
                                <span class="menu-text">Timeline</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'boxplot'])}}" class="side-nav-link">
                                <span class="menu-text">Boxplot</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'treemap'])}}" class="side-nav-link">
                                <span class="menu-text">Treemap</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'pie'])}}" class="side-nav-link">
                                <span class="menu-text">Pie</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'radar'])}}" class="side-nav-link">
                                <span class="menu-text">Radar</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'radialbar'])}}" class="side-nav-link">
                                <span class="menu-text">RadialBar</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'scatter'])}}" class="side-nav-link">
                                <span class="menu-text">Scatter</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'polar-area'])}}" class="side-nav-link">
                                <span class="menu-text">Polar Area</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'sparklines'])}}" class="side-nav-link">
                                <span class="menu-text">Sparklines</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'slope'])}}" class="side-nav-link">
                                <span class="menu-text">Slope</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'funnel'])}}" class="side-nav-link">
                                <span class="menu-text">Funnel</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarForms" aria-expanded="false" aria-controls="sidebarForms"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-list-details"></i></span>
                    <span class="menu-text"> Forms </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarForms">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'elements'])}}" class="side-nav-link">
                                <span class="menu-text">Basic Elements</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'inputmask'])}}" class="side-nav-link">
                                <span class="menu-text">Inputmask</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'picker'])}}" class="side-nav-link">
                                <span class="menu-text">Picker</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'select'])}}" class="side-nav-link">
                                <span class="menu-text">Select</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'range-slider'])}}" class="side-nav-link">
                                <span class="menu-text">Range Slider</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'validation'])}}" class="side-nav-link">
                                <span class="menu-text">Validation</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'wizard'])}}" class="side-nav-link">
                                <span class="menu-text">Wizard</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'fileuploads'])}}" class="side-nav-link">
                                <span class="menu-text">File Uploads</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form', 'editors'])}}" class="side-nav-link">
                                <span class="menu-text">Editors</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['form','layouts'])}}" class="side-nav-link">
                                <span class="menu-text">Layouts</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarTables" aria-expanded="false" aria-controls="sidebarTables"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-table-row"></i></span>
                    <span class="menu-text"> Tables </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarTables">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['tables', 'basic'])}}" class="side-nav-link">
                                <span class="menu-text">Basic Tables</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['tables', 'gridjs'])}}" class="side-nav-link">
                                <span class="menu-text">Gridjs Tables</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['tables', 'datatable'])}}" class="side-nav-link">
                                <span class="menu-text">Datatable Tables</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMaps" aria-expanded="false" aria-controls="sidebarMaps"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-map-2"></i></span>
                    <span class="menu-text"> Maps </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMaps">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['maps', 'google'])}}" class="side-nav-link">
                                <span class="menu-text">Google Maps</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['maps', 'vector'])}}" class="side-nav-link">
                                <span class="menu-text">Vector Maps</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['maps', 'leaflet'])}}" class="side-nav-link">
                                <span class="menu-text">Leaflet Maps</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title mt-2">
                More
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarLayouts" aria-expanded="false" aria-controls="sidebarLayouts"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-layout"></i></span>
                    <span class="menu-text"> Layouts </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarLayouts">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['layouts-eg', 'horizontal'])}}" target="_blank" class="side-nav-link">Horizontal</a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['layouts-eg', 'detached'])}}" target="_blank" class="side-nav-link">Detached</a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['layouts-eg', 'full'])}}" target="_blank" class="side-nav-link">Full View</a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['layouts-eg', 'fullscreen'])}}" target="_blank" class="side-nav-link">Fullscreen View</a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['layouts-eg', 'hover'])}}" target="_blank" class="side-nav-link">Hover Menu</a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['layouts-eg', 'compact'])}}" target="_blank" class="side-nav-link">Compact</a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['layouts-eg', 'icon-view'])}}"  target="_blank" class="side-nav-link">Icon View</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMultiLevel" aria-expanded="false"
                    aria-controls="sidebarMultiLevel" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-share"></i></span>
                    <span class="menu-text"> Multi Level </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMultiLevel">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a data-bs-toggle="collapse" href="#sidebarSecondLevel" aria-expanded="false"
                                aria-controls="sidebarSecondLevel" class="side-nav-link">
                                <span class="menu-text"> Second Level </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <div class="collapse" id="sidebarSecondLevel">
                                <ul class="sub-menu">
                                    <li class="side-nav-item">
                                        <a href="javascript: void(0);" class="side-nav-link">
                                            <span class="menu-text">Item 1</span>
                                        </a>
                                    </li>
                                    <li class="side-nav-item">
                                        <a href="javascript: void(0);" class="side-nav-link">
                                            <span class="menu-text">Item 2</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="side-nav-item">
                            <a data-bs-toggle="collapse" href="#sidebarThirdLevel" aria-expanded="false"
                                aria-controls="sidebarThirdLevel" class="side-nav-link">
                                <span class="menu-text"> Third Level </span>
                                <span class="menu-arrow"></span>
                            </a>
                            <div class="collapse" id="sidebarThirdLevel">
                                <ul class="sub-menu">
                                    <li class="side-nav-item">
                                        <a href="javascript: void(0);" class="side-nav-link">Item 1</a>
                                    </li>
                                    <li class="side-nav-item">
                                        <a data-bs-toggle="collapse" href="#sidebarFourthLevel" aria-expanded="false"
                                            aria-controls="sidebarFourthLevel" class="side-nav-link">
                                            <span class="menu-text"> Item 2 </span>
                                            <span class="menu-arrow"></span>
                                        </a>
                                        <div class="collapse" id="sidebarFourthLevel">
                                            <ul class="sub-menu">
                                                <li class="side-nav-item">
                                                    <a href="javascript: void(0);" class="side-nav-link">
                                                        <span class="menu-text">Item 2.1</span>
                                                    </a>
                                                </li>
                                                <li class="side-nav-item">
                                                    <a href="javascript: void(0);" class="side-nav-link">
                                                        <span class="menu-text">Item 2.2</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

    <div class="clearfix"></div>
    </div>
</div>
<!-- Sidenav Menu End -->
