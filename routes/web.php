<?php

use App\Http\Controllers\RoutingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Auth routes
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->middleware('nocache')->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('rotate.session')->name('login.attempt');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// Temporary diagnostic route (non-production) to help trace persistent 419 issues: shows session + CSRF token
if (config('app.env') !== 'production') {
    Route::get('/_debug/csrf', function(\Illuminate\Http\Request $request) {
        return response()->json([
            'env' => config('app.env'),
            'session_id' => $request->session()->getId(),
            'session_cookie' => request()->cookie(config('session.cookie')),
            'csrf_token_session' => $request->session()->token(),
            'csrf_token_helper' => csrf_token(),
            'expected_cookie_domain' => config('session.domain'),
            'secure_cookie_flag' => config('session.secure'),
            'same_site' => config('session.same_site'),
            'rotated_flag' => $request->session()->get('_rotated'),
        ]);
    });
}

// CRM Routes (protected)
Route::middleware('auth')->prefix('crm')->group(function () {
    // --- OPS module (placed early inside /crm group) ---
    Route::prefix('ops')->group(function(){
        Route::get('/', [\App\Http\Controllers\Ops\DashboardController::class, 'index'])
            ->middleware('can:ops.view')
            ->name('ops.dashboard');
        Route::get('/history', [\App\Http\Controllers\Ops\HistoryController::class, 'index'])
            ->middleware('can:ops.view')
            ->name('ops.history.index');
        Route::post('/actions/{action}', [\App\Http\Controllers\Ops\ActionController::class, 'run'])
            ->middleware(['can:ops.execute','throttle:ops-actions'])
            ->name('ops.action');
        // Trigger rebuild documentation (ops.execute)
        Route::post('/actions/docs-build', [\App\Http\Controllers\Ops\ActionController::class, 'docsBuild'])
            ->middleware(['can:ops.execute','throttle:ops-actions'])
            ->name('ops.docs.build');
        Route::get('/metrics', \App\Http\Controllers\Ops\MetricsController::class)
            ->middleware('can:ops.view')
            ->name('ops.metrics');
    });
    // Order items AJAX
    Route::get('orders/{order}/items', [\App\Http\Controllers\OrderItemsController::class, 'show']);
    // Dashboard
    Route::get('/', function () {
        // Pass AC availability flag for UI components that might rely on it
        $acEnabled = (bool) json_decode((string) optional(\App\Models\SystemSetting::where('key','ac_sync_enabled')->first())->value ?: 'false');
        return view('crm.dashboard', ['acEnabled' => $acEnabled]);
    })->name('crm.dashboard');

    // Companies
    Route::resource('companies', CompanyController::class);
    
    // Contacts
    Route::resource('contacts', ContactController::class);
    
    // Leads
    Route::resource('leads', LeadController::class);
    Route::get('leads-kanban', [LeadController::class, 'kanban'])->name('leads.kanban');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.update-status');
    // Alias to support AJAX POST from views
    Route::post('leads/{lead}/status', [LeadController::class, 'updateStatus']);
    
    // Opportunities
    Route::resource('opportunities', OpportunityController::class);
    Route::get('opportunities-pipeline', [OpportunityController::class, 'pipeline'])->name('opportunities.pipeline');
    
    // Tasks
    Route::resource('tasks', TaskController::class);
    
    // Deals
    Route::resource('deals', DealController::class);

    // Projects
    Route::resource('projects', ProjectController::class);

    // Knowledge (notes)
    Route::get('knowledge', [\App\Http\Controllers\KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::get('knowledge/create', [\App\Http\Controllers\KnowledgeController::class, 'create'])->name('knowledge.create');
    Route::post('knowledge', [\App\Http\Controllers\KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::get('knowledge/{knowledge}/edit', [\App\Http\Controllers\KnowledgeController::class, 'edit'])->name('knowledge.edit');
    Route::put('knowledge/{knowledge}', [\App\Http\Controllers\KnowledgeController::class, 'update'])->name('knowledge.update');
    Route::delete('knowledge/{knowledge}', [\App\Http\Controllers\KnowledgeController::class, 'destroy'])->name('knowledge.destroy');
    Route::get('knowledge/search/ajax', [\App\Http\Controllers\KnowledgeController::class, 'search'])->name('knowledge.search');

    // Knowledge documents (uploads)
    Route::get('knowledge-docs', [\App\Http\Controllers\KnowledgeDocumentsController::class, 'index'])->name('knowledge.docs.index');
    Route::get('knowledge-docs/create', [\App\Http\Controllers\KnowledgeDocumentsController::class, 'create'])->name('knowledge.docs.create');
    Route::post('knowledge-docs', [\App\Http\Controllers\KnowledgeDocumentsController::class, 'store'])->name('knowledge.docs.store');
    Route::post('knowledge-docs/{id}/reindex', [\App\Http\Controllers\KnowledgeDocumentsController::class, 'reindex'])->name('knowledge.docs.reindex');
    Route::post('knowledge-docs/{id}/purge', [\App\Http\Controllers\KnowledgeDocumentsController::class, 'purge'])->name('knowledge.docs.purge');

    // Products (catalog) - read-only for now
    Route::prefix('products')->middleware('can:products.view')->group(function(){
        Route::get('/', [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
        Route::get('{product}', [\App\Http\Controllers\ProductController::class, 'show'])->name('products.show');
    });

    // Orders (read-only MVP) - permission restored now that module stabilised
    Route::prefix('orders')->middleware(env('ORDERS_BYPASS_PERMISSION', false) ? [] : ['can:orders.view'])->group(function(){
        Route::get('/', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
        Route::get('{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
        Route::post('trigger-import', [\App\Http\Controllers\OrderController::class, 'triggerImport'])
            ->middleware(['auth','throttle:ops-actions'])
            ->name('orders.triggerImport');
    });

    // Search (AJAX)
    Route::get('search/customers', [SearchController::class, 'customers'])->name('search.customers');
    Route::get('search/taskables', [SearchController::class, 'taskables'])->name('search.taskables');

    // System > ActiveCampaign (admin)
    Route::prefix('system')->group(function(){
    Route::get('activecampaign', [\App\Http\Controllers\System\ActiveCampaignController::class, 'index'])->name('system.ac.index');
    Route::get('activecampaign/runs', [\App\Http\Controllers\System\ActiveCampaignController::class, 'runs'])->name('system.ac.runs');
        Route::post('activecampaign/test', [\App\Http\Controllers\System\ActiveCampaignController::class, 'test'])->name('system.ac.test');
        Route::post('activecampaign/import-10', [\App\Http\Controllers\System\ActiveCampaignController::class, 'importTen'])->name('system.ac.import10');
    Route::post('activecampaign/import', [\App\Http\Controllers\System\ActiveCampaignController::class, 'importBatch'])->name('system.ac.import');
    Route::post('activecampaign/import-all', [\App\Http\Controllers\System\ActiveCampaignController::class, 'importAll'])->name('system.ac.importAll');
    Route::post('activecampaign/toggle', [\App\Http\Controllers\System\ActiveCampaignController::class, 'toggleAuto'])->name('system.ac.toggle');
    Route::post('activecampaign/reset-offset', [\App\Http\Controllers\System\ActiveCampaignController::class, 'resetOffset'])->name('system.ac.resetOffset');
    Route::post('activecampaign/run-batch', [\App\Http\Controllers\System\ActiveCampaignController::class, 'runBatch'])->name('system.ac.runBatch');

    // System > Backup (admin)
        Route::get('backup', [\App\Http\Controllers\System\BackupController::class, 'index'])->name('system.backup.index');
        Route::post('backup/run', [\App\Http\Controllers\System\BackupController::class, 'run'])->name('system.backup.run');
    Route::post('backup/clean', [\App\Http\Controllers\System\BackupController::class, 'clean'])->name('system.backup.clean');
        Route::get('backup/download/{path}', [\App\Http\Controllers\System\BackupController::class, 'download'])
            ->where('path', '.*')
            ->name('system.backup.download');

    // System > Aplikace (admin)
    Route::get('apps', [\App\Http\Controllers\System\AppsController::class, 'index'])->name('system.apps.index');
    Route::post('apps', [\App\Http\Controllers\System\AppsController::class, 'store'])->name('system.apps.store');
    Route::post('apps/{appLink}', [\App\Http\Controllers\System\AppsController::class, 'update'])->name('system.apps.update');
    Route::post('apps/{appLink}/delete', [\App\Http\Controllers\System\AppsController::class, 'destroy'])->name('system.apps.destroy');
    
        // Chat settings
        Route::get('chat', [\App\Http\Controllers\System\ChatSettingsController::class, 'index'])->name('system.chat.index');
        Route::post('chat', [\App\Http\Controllers\System\ChatSettingsController::class, 'save'])->name('system.chat.save');
        Route::post('chat/test', [\App\Http\Controllers\System\ChatSettingsController::class, 'test'])->name('system.chat.test');
    Route::get('chat/diagnostics', [\App\Http\Controllers\System\ChatSettingsController::class, 'diagnostics'])->name('system.chat.diagnostics');
    Route::get('chat/lookup', [\App\Http\Controllers\System\ChatSettingsController::class, 'lookup'])->name('system.chat.lookup');
    Route::post('chat/backfill', [\App\Http\Controllers\System\ChatSettingsController::class, 'backfillNormalized'])->name('system.chat.backfill');

    // System > Qdrant (admin)
    Route::get('qdrant', [\App\Http\Controllers\System\QdrantController::class, 'index'])->name('system.qdrant.index');
    Route::post('qdrant/test', [\App\Http\Controllers\System\QdrantController::class, 'test'])->name('system.qdrant.test');
    Route::post('qdrant/save', [\App\Http\Controllers\System\QdrantController::class, 'save'])->name('system.qdrant.save');
    Route::post('qdrant/verify', [\App\Http\Controllers\System\QdrantController::class, 'verify'])->name('system.qdrant.verify');
    Route::post('qdrant/recreate', [\App\Http\Controllers\System\QdrantMaintenanceController::class, 'recreate'])->name('system.qdrant.recreate');
    Route::post('qdrant/purge-reindex', [\App\Http\Controllers\System\QdrantMaintenanceController::class, 'purgeReindex'])->name('system.qdrant.purgeReindex');

    // System > Nástroje (admin)
    Route::get('tools', [\App\Http\Controllers\System\ToolsController::class, 'index'])->name('system.tools.index');
    Route::post('tools/playwright/toggle', [\App\Http\Controllers\System\ToolsController::class, 'togglePlaywright'])->name('system.tools.playwright.toggle');
    Route::post('tools/playwright/save', [\App\Http\Controllers\System\ToolsController::class, 'savePlaywright'])->name('system.tools.playwright.save');
    Route::post('tools/playwright/test', [\App\Http\Controllers\System\ToolsController::class, 'testPlaywright'])->name('system.tools.playwright.test');

    // System > Agent V2 enable (admin)
    Route::post('agentv2/enable', [\App\Http\Controllers\System\AgentV2SetupController::class, 'enable'])
        ->name('system.agentv2.enable');

    // Maintenance: web-triggered migrations (admin only)
    Route::post('migrate', [\App\Http\Controllers\System\MaintenanceController::class, 'migrate'])->name('system.migrate');
    });

    // Marketing (preview-only pages)
    Route::prefix('marketing')->group(function () {
        // Dashboard
        Route::get('/', function () { return view('crm.marketing.index'); })->name('marketing.dashboard');

        // Strategie
        Route::prefix('strategie')->group(function () {
            Route::get('kalendar', fn() => view('crm.marketing.strategie.kalendar'))
                ->name('marketing.strategy.calendar');
            Route::get('budget', fn() => view('crm.marketing.strategie.budget'))
                ->name('marketing.strategy.budget');
            Route::get('persony', fn() => view('crm.marketing.strategie.persony'))
                ->name('marketing.strategy.personas');
            Route::get('swot', fn() => view('crm.marketing.strategie.swot'))
                ->name('marketing.strategy.swot');
            Route::get('trendy-ai', fn() => view('crm.marketing.strategie.trendy-ai'))
                ->name('marketing.strategy.trends');
        });

        // Exekuce
        Route::prefix('exekuce')->group(function () {
            Route::get('kampane', fn() => view('crm.marketing.exekuce.kampane'))
                ->name('marketing.exec.campaigns');
            Route::get('automatizace', fn() => view('crm.marketing.exekuce.automatizace'))
                ->name('marketing.exec.automation');
            Route::get('knihovna-obsahu', fn() => view('crm.marketing.exekuce.knihovna-obsahu'))
                ->name('marketing.exec.content');
            Route::get('reklamy', fn() => view('crm.marketing.exekuce.reklamy'))
                ->name('marketing.exec.ads');
            Route::get('email-marketing', fn() => view('crm.marketing.exekuce.email-marketing'))
                ->name('marketing.exec.email');
            Route::get('influenceri-partneri', fn() => view('crm.marketing.exekuce.influenceri-partneri'))
                ->name('marketing.exec.influencers');
            Route::get('landing-pages', fn() => view('crm.marketing.exekuce.landing-pages'))
                ->name('marketing.exec.landing');
        });

        // Kontakty a Cílení
        Route::prefix('cileni')->group(function () {
            Route::get('databaze-kontaktu', fn() => view('crm.marketing.cileni.databaze-kontaktu'))
                ->name('marketing.target.contacts');
            Route::get('segmentace', fn() => view('crm.marketing.cileni.segmentace'))
                ->name('marketing.target.segments');
            Route::get('lead-nurturing', fn() => view('crm.marketing.cileni.lead-nurturing'))
                ->name('marketing.target.nurturing');
        });

        // Analytika a Reporty
        Route::prefix('analytika')->group(function () {
            Route::get('seo', fn() => view('crm.marketing.analytika.seo'))
                ->name('marketing.analytics.seo');
            Route::get('atribuce', fn() => view('crm.marketing.analytika.atribuce'))
                ->name('marketing.analytics.attribution');
            Route::get('cross-channel', fn() => view('crm.marketing.analytika.cross-channel'))
                ->name('marketing.analytics.cross');
            Route::get('ab-testovani', fn() => view('crm.marketing.analytika.ab-testovani'))
                ->name('marketing.analytics.ab');
            Route::get('ai-sentiment', fn() => view('crm.marketing.analytika.ai-sentiment'))
                ->name('marketing.analytics.sentiment');
        });

        // Nastavení
        Route::prefix('nastaveni')->group(function () {
            Route::get('integrace-api', fn() => view('crm.marketing.nastaveni.integrace-api'))
                ->name('marketing.settings.integrations');
            Route::get('lead-scoring', fn() => view('crm.marketing.nastaveni.lead-scoring'))
                ->name('marketing.settings.scoring');
            Route::get('role-prava', fn() => view('crm.marketing.nastaveni.role-prava'))
                ->name('marketing.settings.roles');
            Route::get('ai-sablony', fn() => view('crm.marketing.nastaveni.ai-sablony'))
                ->name('marketing.settings.ai');
            // Importy sekce
            Route::get('importy', [\App\Http\Controllers\Settings\ImportsController::class, 'index'])
                ->name('settings.imports');
        });
    });

    // Chat (rate-limited)
    Route::prefix('chat')->middleware('throttle:30,1')->group(function(){
        Route::get('/', [ChatController::class, 'page'])->name('crm.chat');
    Route::get('sessions', [ChatController::class, 'sessions']);
        Route::post('sessions', [ChatController::class, 'createSession']);
        Route::post('messages', [ChatController::class, 'postMessage']);
        Route::get('stream', [ChatController::class, 'stream']);
        Route::get('sessions/{id}/messages', [ChatController::class, 'history']);
    });
});

// Protect the template index behind login
Route::middleware('auth')->get('/index', function () {
    return view('index');
})->name('index');

// Apps: user management (admin only)
Route::middleware(['auth'])->group(function () {
    // Self Profile
    Route::get('/apps/user-profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('apps.me.show');
    Route::post('/apps/user-profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('apps.me.update');

    // Admin-managed users
    Route::get('/apps/user-contacts', [\App\Http\Controllers\UserManagementController::class, 'index'])->name('apps.users.index');
    Route::get('/apps/user-profile/{user}', [\App\Http\Controllers\UserManagementController::class, 'edit'])->name('apps.users.edit');
    Route::post('/apps/user-profile/{user}', [\App\Http\Controllers\UserManagementController::class, 'update'])->name('apps.users.update');
});

// Default Adminto routes
Route::get('', [RoutingController::class, 'index'])->name('root');
Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
Route::get('{any}', [RoutingController::class, 'root'])->name('any');
