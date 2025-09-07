<!-- Sidenav Menu Start -->
<div class="sidenav-menu">

    <!-- Brand Logo -->
    <a href="{{ route('crm.dashboard')}}" class="logo">
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

    <!-- Custom CRM Menu Styles -->
    <style>
    .sidenav-menu .logo .logo-lg img { height:60px; max-height:60px; width:auto; }
        .side-nav-item .side-nav-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            color: #9ba3af !important;
        }
        
        .side-nav-item .side-nav-link.disabled:hover {
            background-color: transparent !important;
            color: #9ba3af !important;
        }
        
        .side-nav-item .side-nav-link.disabled .menu-icon {
            color: #9ba3af !important;
        }
        
        .badge.bg-warning {
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }

        .side-nav-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            padding: 0 1rem;
        }
        
        .side-nav-item .side-nav-link {
            transition: all 0.3s ease;
        }
        
        .side-nav-item .side-nav-link:hover {
            transform: translateX(2px);
        }
    </style>

    <div data-simplebar>
        <!-- User panel removed (duplicated with topbar user menu) -->

        <!--- Sidenav Menu -->
        <ul class="side-nav">
            <!-- CRM Dashboard -->
            <li class="side-nav-item">
                <a href="{{ url('/crm') }}" class="side-nav-link {{ request()->routeIs('crm.dashboard') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                    <span class="menu-text"> CRM Dashboard </span>
                </a>
            </li>

            <li class="side-nav-title mt-2">
                CRM
            </li>

            <!-- Companies -->
            <li class="side-nav-item">
                <a href="{{ url('/crm/companies') }}" class="side-nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-building"></i></span>
                    <span class="menu-text"> Společnosti </span>
                </a>
            </li>

            <!-- Contacts -->
            <li class="side-nav-item">
                <a href="{{ url('/crm/contacts') }}" class="side-nav-link {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-user-square-rounded"></i></span>
                    <span class="menu-text"> Kontakty </span>
                </a>
            </li>

            <!-- Leads -->
            <li class="side-nav-item">
                <a href="{{ url('/crm/leads') }}" class="side-nav-link {{ request()->routeIs('leads.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-target"></i></span>
                    <span class="menu-text"> Leads </span>
                </a>
            </li>

            <!-- Opportunities -->
            <li class="side-nav-item">
                <a href="{{ route('opportunities.index') }}" class="side-nav-link {{ request()->routeIs('opportunities.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-currency-dollar"></i></span>
                    <span class="menu-text"> Příležitosti </span>
                </a>
            </li>

            @can('ops.view')
            <!-- Ops (Git & Zálohy) -->
            <li class="side-nav-item">
                @php($opsActive = request()->is('crm/ops*'))
                <a data-bs-toggle="collapse" href="#sidebarOps" aria-expanded="{{ $opsActive ? 'true':'false' }}" aria-controls="sidebarOps" class="side-nav-link {{ $opsActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ri-tools-line"></i></span>
                    <span class="menu-text"> Ops (Git &amp; Zálohy) </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $opsActive ? 'show' : '' }}" id="sidebarOps">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('ops.dashboard') }}" class="side-nav-link">Dashboard</a></li>
                        <li class="side-nav-item"><a href="{{ route('ops.history.index') }}" class="side-nav-link">Historie</a></li>
                    </ul>
                </div>
            </li>
            @endcan


            <!-- Deals -->
            <li class="side-nav-item">
                <a href="{{ route('deals.index') }}" class="side-nav-link {{ request()->routeIs('deals.*') ? 'active' : '' }}">
                    <span class="menu-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M16 16l-2.5 2.5a2.8 2.8 0 0 1-4 0l-3.2-3.2a1.8 1.8 0 0 1 2.6-2.6l1.6 1.6" />
                            <path d="M8 8l2.5-2.5a2.8 2.8 0 0 1 4 0l3.2 3.2a1.8 1.8 0 0 1-2.6 2.6l-1.6-1.6" />
                            <path d="M3 8h2" />
                            <path d="M19 16h2" />
                            <path d="M3 16h2" />
                            <path d="M19 8h2" />
                        </svg>
                    </span>
                    <span class="menu-text"> Obchody </span>
                </a>
            </li>

            <li class="side-nav-title mt-2">Řízení projektů</li>

            <!-- Projects (moved) -->
            <li class="side-nav-item">
                <a href="{{ route('projects.index') }}" class="side-nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-briefcase"></i></span>
                    <span class="menu-text"> Projekty </span>
                </a>
            </li>

            <!-- Tasks (moved) -->
            <li class="side-nav-item">
                <a href="{{ route('tasks.index') }}" class="side-nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-kanban"></i></span>
                    <span class="menu-text"> Úkoly </span>
                </a>
            </li>

            <li class="side-nav-title mt-2">Ecommerce</li>

            <!-- Orders (submenu) -->
            @php($ordersActive = request()->routeIs('orders.*'))
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarOrders" aria-expanded="{{ $ordersActive ? 'true':'false' }}" aria-controls="sidebarOrders" class="side-nav-link {{ $ordersActive ? 'active' : '' }} {{ !auth()->user() || !auth()->user()->can('orders.view') ? 'disabled' : '' }}">
                    <span class="menu-icon"><i class="ti ti-shopping-cart"></i></span>
                    <span class="menu-text"> Objednávky </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $ordersActive ? 'show' : '' }}" id="sidebarOrders">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('orders.index') }}" class="side-nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}">Přehled</a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('orders.sync.settings') }}" class="side-nav-link {{ request()->routeIs('orders.sync.settings') ? 'active' : '' }}">Nastavení synchronizace</a>
                        </li>
                    </ul>
                </div>
            </li>

            @can('products.view')
            <!-- Products (moved) -->
            <li class="side-nav-item">
                <a href="{{ route('products.index') }}" class="side-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-package"></i></span>
                    <span class="menu-text"> Produkty </span>
                </a>
            </li>
            @endcan

            <li class="side-nav-title mt-2">Znalosti</li>

            <!-- Knowledge: Notes -->
            <li class="side-nav-item">
                <a href="{{ route('knowledge.index') }}" class="side-nav-link {{ request()->routeIs('knowledge.*') && !request()->routeIs('knowledge.docs.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-book"></i></span>
                    <span class="menu-text"> Znalosti </span>
                </a>
            </li>

            <!-- Knowledge: Documents -->
            <li class="side-nav-item">
                <a href="{{ route('knowledge.docs.index') }}" class="side-nav-link {{ request()->routeIs('knowledge.docs.*') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-file-text"></i></span>
                    <span class="menu-text"> Znalostní dokumenty </span>
                </a>
            </li>

            @can('products.view')
                <!-- Products Catalog (moved to CRM section) -->
            @endcan

            <!-- Marketing -->
            <li class="side-nav-title mt-2">Marketing</li>

            <!-- Marketing Dashboard -->
            <li class="side-nav-item">
                <a href="{{ route('marketing.dashboard') }}" class="side-nav-link {{ request()->routeIs('marketing.*') && request()->routeIs('marketing.dashboard') ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-bullhorn"></i></span>
                    <span class="menu-text"> Marketing Dashboard </span>
                </a>
            </li>

            <!-- Strategie -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMkStrategie" aria-expanded="false"
                    aria-controls="sidebarMkStrategie" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-target"></i></span>
                    <span class="menu-text"> Strategie</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMkStrategie">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.calendar') }}" class="side-nav-link"><span class="menu-text">Marketingový kalendář</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.budget') }}" class="side-nav-link"><span class="menu-text">Správa Budgetu</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.personas') }}" class="side-nav-link"><span class="menu-text">Cílové Persony</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.swot') }}" class="side-nav-link"><span class="menu-text">SWOT & konkurence</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.strategy.trends') }}" class="side-nav-link"><span class="menu-text">Trendy & AI</span></a></li>
                    </ul>
                </div>
            </li>

            <!-- Exekuce -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMkExekuce" aria-expanded="false"
                    aria-controls="sidebarMkExekuce" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-player-play"></i></span>
                    <span class="menu-text"> Exekuce</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMkExekuce">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.campaigns') }}" class="side-nav-link"><span class="menu-text">Kampaně</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.automation') }}" class="side-nav-link"><span class="menu-text">Automatizace</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.content') }}" class="side-nav-link"><span class="menu-text">Knihovna obsahu</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.ads') }}" class="side-nav-link"><span class="menu-text">Reklamy (PPC & Social)</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.email') }}" class="side-nav-link"><span class="menu-text">E-mail marketing</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.influencers') }}" class="side-nav-link"><span class="menu-text">Influenceři & partneři</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.exec.landing') }}" class="side-nav-link"><span class="menu-text">Landing Pages</span></a></li>
                    </ul>
                </div>
            </li>

            <!-- Kontakty & Cílení -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMkCileni" aria-expanded="false"
                    aria-controls="sidebarMkCileni" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-users"></i></span>
                    <span class="menu-text"> Kontakty & Cílení</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMkCileni">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.target.contacts') }}" class="side-nav-link"><span class="menu-text">Databáze kontaktů</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.target.segments') }}" class="side-nav-link"><span class="menu-text">Segmentace</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.target.nurturing') }}" class="side-nav-link"><span class="menu-text">Lead Nurturing</span></a></li>
                    </ul>
                </div>
            </li>

            <!-- Analytika & Reporty -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMkAnalytika" aria-expanded="false"
                    aria-controls="sidebarMkAnalytika" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-chart-bar"></i></span>
                    <span class="menu-text"> Analytika & Reporty</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMkAnalytika">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.seo') }}" class="side-nav-link"><span class="menu-text">SEO Přehled</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.attribution') }}" class="side-nav-link"><span class="menu-text">Atribuce konverzí</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.cross') }}" class="side-nav-link"><span class="menu-text">Cross-channel</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.ab') }}" class="side-nav-link"><span class="menu-text">A/B testování</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.analytics.sentiment') }}" class="side-nav-link"><span class="menu-text">AI Sentiment</span></a></li>
                    </ul>
                </div>
            </li>

            <!-- Nastavení -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarMkNastaveni" aria-expanded="false"
                    aria-controls="sidebarMkNastaveni" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-settings"></i></span>
                    <span class="menu-text"> Nastavení</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarMkNastaveni">
                    <ul class="sub-menu">
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.integrations') }}" class="side-nav-link"><span class="menu-text">Integrace & API</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.scoring') }}" class="side-nav-link"><span class="menu-text">Lead Scoring</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.roles') }}" class="side-nav-link"><span class="menu-text">Role & práva</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('marketing.settings.ai') }}" class="side-nav-link"><span class="menu-text">AI šablony</span></a></li>
                        <li class="side-nav-item"><a href="{{ route('settings.imports') }}" class="side-nav-link"><span class="menu-text">Importy</span></a></li>
                    </ul>
                </div>
            </li>

            <!-- Calendar -->
            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'calendar'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-calendar"></i></span>
                    <span class="menu-text"> Kalendář </span>
                </a>
            </li>

            <!-- Email -->
            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'email'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-mailbox"></i></span>
                    <span class="menu-text"> Email </span>
                </a>
            </li>

            <!-- File Manager -->
            <li class="side-nav-item">
                <a href="{{ route('second', ['apps', 'file-manager'])}}" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-folders"></i></span>
                    <span class="menu-text"> Správce souborů </span>
                </a>
            </li>

            <li class="side-nav-title mt-2">
                Administrace
            </li>

            <!-- Users -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarUsers" aria-expanded="false"
                    aria-controls="sidebarUsers" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-users"></i></span>
                    <span class="menu-text"> Uživatelé</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarUsers">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'user-contacts'])}}" class="side-nav-link">
                                <span class="menu-text">Seznam uživatelů</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['apps', 'user-profile'])}}" class="side-nav-link">
                                <span class="menu-text">Profil</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Analytics -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarReports" aria-expanded="false" aria-controls="sidebarReports"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-chart-infographic"></i></span>
                    <span class="menu-text"> Reporty </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarReports">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'area'])}}" class="side-nav-link">
                                <span class="menu-text">Sales Analytics</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'bar'])}}" class="side-nav-link">
                                <span class="menu-text">Lead Reports</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['charts', 'pie'])}}" class="side-nav-link">
                                <span class="menu-text">Revenue Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title mt-2">
                Systém
            </li>

            <!-- System Settings -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarSettings" aria-expanded="{{ request()->routeIs('system.ac.*') || request()->routeIs('system.backup.*') || request()->routeIs('system.apps.*') || request()->routeIs('system.chat.*') || request()->routeIs('system.qdrant.*') || request()->routeIs('system.tools.*') ? 'true' : 'false' }}"
                    aria-controls="sidebarSettings" class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-settings"></i></span>
                    <span class="menu-text"> Nastavení </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ request()->routeIs('system.ac.*') || request()->routeIs('system.backup.*') || request()->routeIs('system.apps.*') || request()->routeIs('system.chat.*') || request()->routeIs('system.qdrant.*') || request()->routeIs('system.tools.*') ? 'show' : '' }}" id="sidebarSettings">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('system.ac.index') }}" class="side-nav-link {{ request()->routeIs('system.ac.*') ? 'active' : '' }}">
                                <span class="menu-text">ActiveCampaign</span>
                                <span class="badge bg-warning ms-2">beta</span>
                            </a>
                        </li>
                        @if(auth()->check() && (bool) (auth()->user()->is_admin ?? false))
                        <li class="side-nav-item">
                            <a href="{{ route('system.apps.index') }}" class="side-nav-link {{ request()->routeIs('system.apps.*') ? 'active' : '' }}">
                                <span class="menu-text">Aplikace</span>
                                <span class="badge bg-success ms-2">nové</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('system.chat.index') }}" class="side-nav-link {{ request()->routeIs('system.chat.*') ? 'active' : '' }}">
                                <span class="menu-text">Chat</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('system.qdrant.index') }}" class="side-nav-link {{ request()->routeIs('system.qdrant.*') ? 'active' : '' }}">
                                <span class="menu-text">Qdrant</span>
                                <span class="badge bg-info ms-2">RAG</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('system.tools.index') }}" class="side-nav-link {{ request()->routeIs('system.tools.*') ? 'active' : '' }}">
                                <span class="menu-text">Nástroje</span>
                            </a>
                        </li>
                        @endif
                        <li class="side-nav-item">
                            <a href="javascript:void(0);" class="side-nav-link">
                                <span class="menu-text">Obecné nastavení</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="javascript:void(0);" class="side-nav-link">
                                <span class="menu-text">Email konfigurace</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="javascript:void(0);" class="side-nav-link">
                                <span class="menu-text">Oprávnění</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Help & Support -->
            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarSupport" aria-expanded="false" aria-controls="sidebarSupport"
                    class="side-nav-link">
                    <span class="menu-icon"><i class="ti ti-help"></i></span>
                    <span class="menu-text"> Nápověda </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarSupport">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('second', ['pages', 'faq'])}}" class="side-nav-link">
                                <span class="menu-text">FAQ</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="javascript:void(0);" class="side-nav-link">
                                <span class="menu-text">Dokumentace</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="javascript:void(0);" class="side-nav-link">
                                <span class="menu-text">Kontakt</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

    <div class="clearfix"></div>
    </div>
</div>
<!-- Sidenav Menu End -->
