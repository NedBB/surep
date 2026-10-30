<?php

use Livewire\Volt\Component;
use App\Models\State;
use App\Models\Beneficiary;
use App\Models\Phc;
use App\Models\Activity;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $states = [];
    public $selectedState = null;
    public $showDrawer = false;
    public $stateDetails = [];
    public $totalBeneficiaries = 0;
    public $totalPhcs = 0;
    public $activeBeneficiaries = 0;
    public $loading = false;
    public $stateChartData = [];
    public $zoneChartData = [];
    public $growthChartData = [];

    protected $listeners = ['state-clicked' => 'handleStateClick'];

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function handleStateClick($stateId)
    {
        $this->selectState($stateId);
    }

    public function loadDashboardData()
    {
        // Optimize: Get all beneficiary counts per state in a single query
        $beneficiaryStats = Beneficiary::selectRaw('
                state_id,
                COUNT(*) as total_beneficiaries,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_beneficiaries,
                SUM(CASE WHEN status IN ("dropped_out", "deceased", "expired") THEN 1 ELSE 0 END) as exited_beneficiaries
            ')
            ->groupBy('state_id')
            ->get()
            ->keyBy('state_id');

        // Optimize: Get all PHC counts per state in a single query
        $phcStats = Phc::selectRaw('
                state_id,
                COUNT(*) as phc_count
            ')
            ->where('is_active', true)
            ->groupBy('state_id')
            ->get()
            ->keyBy('state_id');

        // Load all states with their statistics
        $this->states = State::with('zone')
            ->get()
            ->map(function ($state) use ($beneficiaryStats, $phcStats) {
                // Parse map JSON if it's a string
                $mapData = $state->map;
                if (is_string($mapData)) {
                    $mapData = json_decode($mapData, true);
                }
                
                // Get pre-calculated stats from aggregated queries
                $beneficiaryStat = $beneficiaryStats->get($state->id);
                $phcStat = $phcStats->get($state->id);
                
                return [
                    'id' => $state->id,
                    'name' => $state->name,
                    'code' => $state->code,
                    'map' => $mapData, // Include GeoJSON geometry as object
                    'total_beneficiaries' => $beneficiaryStat ? (int)$beneficiaryStat->total_beneficiaries : 0,
                    'total_cash_disbursed' => number_format($state->total_cash_disbursed, 2),
                    'phc_count' => $phcStat ? (int)$phcStat->phc_count : 0,
                    'active_beneficiaries' => $beneficiaryStat ? (int)$beneficiaryStat->active_beneficiaries : 0,
                    'exited_beneficiaries' => $beneficiaryStat ? (int)$beneficiaryStat->exited_beneficiaries : 0,
                    'color' => $state->getHeatmapColor(),
                ];
            })->toArray();

        // Calculate national totals
        $this->totalBeneficiaries = Beneficiary::count();
        $this->totalPhcs = Phc::where('is_active', true)->count();
        $this->activeBeneficiaries = Beneficiary::where('status', 'active')->count();

        // Prepare chart data for states (only states with data)
        $this->stateChartData = collect($this->states)
            ->filter(function ($state) {
                return $state['total_beneficiaries'] > 0;
            })
            ->sortByDesc('total_beneficiaries')
            ->map(function ($state) {
                return [
                    'name' => $state['name'],
                    'total_beneficiaries' => $state['total_beneficiaries'],
                    'active_beneficiaries' => $state['active_beneficiaries'],
                    'phc_count' => $state['phc_count'],
                ];
            })
            ->values()
            ->toArray();

        // Prepare chart data for zones (beneficiaries grouped by zone)
        $zoneStats = Beneficiary::join('states', 'beneficiaries.state_id', '=', 'states.id')
            ->join('zones', 'states.zone_id', '=', 'zones.id')
            ->selectRaw('
                zones.id as zone_id,
                zones.name as zone_name,
                COUNT(*) as total_beneficiaries,
                SUM(CASE WHEN beneficiaries.status = "active" THEN 1 ELSE 0 END) as active_beneficiaries
            ')
            ->groupBy('zones.id', 'zones.name')
            ->get();

        $this->zoneChartData = $zoneStats
            ->filter(function ($zone) {
                return $zone->total_beneficiaries > 0;
            })
            ->sortByDesc('total_beneficiaries')
            ->map(function ($zone) {
                return [
                    'name' => $zone->zone_name,
                    'total_beneficiaries' => (int)$zone->total_beneficiaries,
                    'active_beneficiaries' => (int)$zone->active_beneficiaries,
                ];
            })
            ->values()
            ->toArray();

        // Calculate growth data for years 2013, 2014, 2015 using date_registered
        $years = [2013, 2014, 2015];
        
        $growthData = Beneficiary::selectRaw('
                YEAR(date_registered) as year,
                COUNT(*) as total_beneficiaries
            ')
            ->whereIn(DB::raw('YEAR(date_registered)'), $years)
            ->whereNotNull('date_registered')
            ->groupBy(DB::raw('YEAR(date_registered)'))
            ->get()
            ->keyBy('year');

        // Calculate totals and percentages
        $yearData = collect($years)
            ->map(function ($year) use ($growthData) {
                $data = $growthData->get($year);
                return [
                    'year' => $year,
                    'total_beneficiaries' => $data ? (int)$data->total_beneficiaries : 0,
                ];
            })
            ->values()
            ->toArray();

        // Calculate total across all 3 years
        $totalBeneficiaries = array_sum(array_column($yearData, 'total_beneficiaries'));

        // Calculate percentages
        $this->growthChartData = collect($yearData)
            ->map(function ($item, $index) use ($totalBeneficiaries) {
                $percentage = $totalBeneficiaries > 0 
                    ? ($item['total_beneficiaries'] / $totalBeneficiaries) * 100 
                    : 0;
                return [
                    'label' => 'Year ' . ($index + 1),
                    'total_beneficiaries' => $item['total_beneficiaries'],
                    'percentage' => round($percentage, 1),
                ];
            })
            ->values()
            ->toArray();
    }

    public function selectState($stateId)
    {
        $this->selectedState = $stateId;
        $this->showDrawer = true; // Show modal immediately
        $this->loading = true; // Set loading state
        $this->stateDetails = []; // Clear previous data
        $this->loadStateDetails($stateId);
    }

    public function loadStateDetails($stateId)
    {
        $state = State::with(['lgas'])->find($stateId);
        
        if (!$state) return;

        // Optimize: Get all service counts in a single query using conditional aggregation
        // Count distinct beneficiaries who attended each service
        $serviceCounts = Activity::join('beneficiaries', 'activities.beneficiary_id', '=', 'beneficiaries.id')
            ->where('beneficiaries.state_id', $stateId)
            ->selectRaw('
                COUNT(DISTINCT CASE WHEN activities.anc1_attended = 1 THEN activities.beneficiary_id END) as anc1_count,
                COUNT(DISTINCT CASE WHEN activities.anc2_attended = 1 THEN activities.beneficiary_id END) as anc2_count,
                COUNT(DISTINCT CASE WHEN activities.anc3_attended = 1 THEN activities.beneficiary_id END) as anc3_count,
                COUNT(DISTINCT CASE WHEN activities.anc4_attended = 1 THEN activities.beneficiary_id END) as anc4_count,
                COUNT(DISTINCT CASE WHEN activities.skilled_delivery_attended = 1 THEN activities.beneficiary_id END) as skilled_delivery_count,
                COUNT(DISTINCT CASE WHEN activities.post_natal_attended = 1 THEN activities.beneficiary_id END) as post_natal_count,
                COUNT(DISTINCT CASE WHEN activities.immunization_attended = 1 THEN activities.beneficiary_id END) as immunization_count
            ')
            ->first();

        // Calculate actual counts from beneficiaries table
        $actualTotalBeneficiaries = Beneficiary::where('state_id', $stateId)->count();
        $actualActiveBeneficiaries = Beneficiary::where('state_id', $stateId)->where('status', 'active')->count();
        $actualExitedBeneficiaries = Beneficiary::where('state_id', $stateId)->whereIn('status', ['dropped_out', 'deceased', 'expired'])->count();
        $actualPhcCount = Phc::where('state_id', $stateId)->where('is_active', true)->count();
        
        $this->stateDetails = [
            'name' => $state->name,
            'total_beneficiaries' => $actualTotalBeneficiaries,
            'active_beneficiaries' => $actualActiveBeneficiaries,
            'exited_beneficiaries' => $actualExitedBeneficiaries,
            'total_disbursed' => number_format($state->total_cash_disbursed, 2),
            'phc_count' => $actualPhcCount,
            'anc_visits' => [
                'anc1' => (int) ($serviceCounts->anc1_count ?? 0),
                'anc2' => (int) ($serviceCounts->anc2_count ?? 0),
                'anc3' => (int) ($serviceCounts->anc3_count ?? 0),
                'anc4' => (int) ($serviceCounts->anc4_count ?? 0),
            ],
            'services' => [
                'skilled_delivery' => (int) ($serviceCounts->skilled_delivery_count ?? 0),
                'post_natal' => (int) ($serviceCounts->post_natal_count ?? 0),
                'immunization' => (int) ($serviceCounts->immunization_count ?? 0),
            ],
            'lgas' => $state->lgas->pluck('name')->toArray(),
            'phcs' => Phc::where('state_id', $stateId)
                ->where('is_active', true)
                ->take(10)
                ->get()
                ->map(fn($phc) => [
                    'name' => $phc->name,
                    'beneficiaries' => $phc->total_beneficiaries
                ])->toArray(),
        ];

        $this->loading = false;
        $this->dispatch('state-details-loaded', stateDetails: $this->stateDetails);
    }

    public function closeDrawer()
    {
        $this->showDrawer = false;
        $this->selectedState = null;
        $this->loading = false;
        $this->stateDetails = [];
    }
}; ?>

<div class="py-6">
        <!-- Interactive Map Section -->
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Interactive Map of Nigeria</h2>
                    <p class="text-sm text-gray-600">Hover over states to see statistics, click to view detailed information</p>
                </div>

                <!-- States Grid -->
                <div class="relative">
                    <livewire:nigeria-map :states="$states" />
                </div>

                <!-- Legend -->
                <div class="mt-6 flex items-center justify-center flex-wrap gap-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded" style="background-color: #d1d5db;"></div>
                        <span class="text-sm text-gray-600">No Activity</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded" style="background-color: #90caf9;"></div>
                        <span class="text-sm text-gray-600">Low (&lt;50)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded" style="background-color: #42a5f5;"></div>
                        <span class="text-sm text-gray-600">Medium (50-200)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded" style="background-color: #1976d2;"></div>
                        <span class="text-sm text-gray-600">High (&gt;200)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- States Chart Section -->
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Statistics Overview</h2>
                    <p class="text-sm text-gray-600">Beneficiaries by zone and state</p>
                </div>
                <div class="flex flex-row gap-6">
                    <!-- Pie Chart Column -->
                    <div class="flex-1 bg-gray-50 p-4 rounded-lg" style="height: 500px;" 
                         x-data="{ chartInitialized: false }"
                         x-init="
                            if (!chartInitialized) {
                                setTimeout(() => {
                                    initializeStatesPieChart();
                                    chartInitialized = true;
                                }, 300);
                            }
                         ">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Beneficiaries by Zone</h3>
                        <canvas id="statesPieChart"></canvas>
                    </div>
                    
                    <!-- Bar Chart Column -->
                    <div class="flex-1 bg-gray-50 p-4 rounded-lg" style="height: 500px;" 
                         x-data="{ chartInitialized: false }"
                         x-init="
                            if (!chartInitialized) {
                                setTimeout(() => {
                                    initializeStatesBarChart();
                                    chartInitialized = true;
                                }, 300);
                            }
                         ">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">State to Beneficiary Data</h3>
                        <canvas id="statesBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Growth Chart Section -->
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Growth Trend</h2>
                    <p class="text-sm text-gray-600">Beneficiary registrations by year showing percentage distribution</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg" style="height: 400px;" 
                     x-data="{ chartInitialized: false }"
                     x-init="
                        if (!chartInitialized) {
                            setTimeout(() => {
                                initializeGrowthChart();
                                chartInitialized = true;
                            }, 300);
                        }
                     ">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- State Details Modal -->
        <div x-data="{ showModal: false }"
             x-show="showModal || $wire.showDrawer"
             @state-selected.window="showModal = true"
             x-init="
                $watch('$wire.showDrawer', value => {
                    if (value) {
                        showModal = true;
                    } else {
                        showModal = false;
                    }
                });
             "
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background-color: rgba(0, 0, 0, 0.5);"
             @click.self="showModal = false; $wire.closeDrawer();"
             x-cloak>
            
            <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-fit max-h-[90vh] overflow-y-auto"
                 x-transition:enter="transition-all ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition-all ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 x-init="
                    $watch('$wire.showDrawer', value => { 
                        if(value && $wire.selectedState && $wire.stateDetails && Object.keys($wire.stateDetails).length > 0) { 
                            setTimeout(() => initializeCharts(), 200); 
                        } else if (!value) { 
                            destroyCharts(); 
                        } 
                    });
                    $watch('$wire.stateDetails', value => {
                        if ($wire.showDrawer && value && Object.keys(value).length > 0) {
                            setTimeout(() => initializeCharts(), 100);
                        }
                    });
                 ">
            
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        @if(!empty($stateDetails['name']))
                            {{ $stateDetails['name'] }}
                        @elseif($selectedState)
                            Loading...
                        @else
                            State Details
                        @endif
                    </h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" @click.stop="$wire.showDrawer = false; $wire.closeDrawer();">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Loading Spinner -->
                @if($loading || empty($stateDetails))
                    <div class="flex flex-col items-center justify-center py-20">
                        <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                        <p class="mt-4 text-gray-600 font-medium">Loading state details...</p>
                        </div>
                @endif

                @if($selectedState && !empty($stateDetails) && !$loading)
                        <!-- State Statistics Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <p class="text-xs font-medium text-blue-600 uppercase">Total Beneficiaries</p>
                                <p class="text-2xl font-bold text-blue-900 mt-1">{{ number_format($stateDetails['total_beneficiaries']) }}</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <p class="text-xs font-medium text-green-600 uppercase">Active</p>
                                <p class="text-2xl font-bold text-green-900 mt-1">{{ number_format($stateDetails['active_beneficiaries']) }}</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <p class="text-xs font-medium text-purple-600 uppercase">PHCs</p>
                                <p class="text-2xl font-bold text-purple-900 mt-1">{{ number_format($stateDetails['phc_count']) }}</p>
                        </div>
                    </div>

                    <!-- ANC Visits Chart -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">ANC Visits</h4>
                            <div class="bg-gray-50 p-4 rounded-lg" style="height: 300px;">
                                <canvas id="ancChart"></canvas>
                        </div>
                    </div>

                        <!-- Other Services Chart -->
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Other Services</h4>
                            <div class="bg-gray-50 p-4 rounded-lg" style="height: 300px;">
                                <canvas id="servicesChart"></canvas>
                                </div>
                            </div>

                    @endif
                    </div>
                    </div>
            </div>
        </div>

        <!-- Initialize Charts when Modal Opens -->
        <script>
            let ancChart = null;
            let servicesChart = null;
            let statesPieChart = null;
            let statesBarChart = null;
            let growthChart = null;
            let latestStateDetails = @json($stateDetails ?? (object) []);
            let stateChartData = @json($stateChartData ?? []);
            let zoneChartData = @json($zoneChartData ?? []);
            let growthChartData = @json($growthChartData ?? []);

            window.addEventListener('state-details-loaded', event => {
                latestStateDetails = event.detail.stateDetails || {};
                console.log('Received state details from Livewire:', latestStateDetails);
                setTimeout(() => initializeCharts(), 100);
            });

            function destroyCharts() {
                // Destroy ANC Chart
                if (ancChart) {
                    ancChart.destroy();
                    ancChart = null;
                }
                // Check if Chart.js has a registered chart for this canvas
                const ancCtx = document.getElementById('ancChart');
                if (ancCtx) {
                    const existingAncChart = Chart.getChart(ancCtx);
                    if (existingAncChart) {
                        existingAncChart.destroy();
                    }
                }
                
                // Destroy Services Chart
                if (servicesChart) {
                    servicesChart.destroy();
                    servicesChart = null;
                }
                // Check if Chart.js has a registered chart for this canvas
                const servicesCtx = document.getElementById('servicesChart');
                if (servicesCtx) {
                    const existingServicesChart = Chart.getChart(servicesCtx);
                    if (existingServicesChart) {
                        existingServicesChart.destroy();
                    }
                }
            }

            function initializeCharts() {
                destroyCharts();
                
                // Wait for DOM to be ready - reduced delay for faster loading
                setTimeout(() => {
                    if (!latestStateDetails || Object.keys(latestStateDetails).length === 0) {
                        console.warn('No state details available for charts yet.');
                        return;
                    }

                    // ANC Visits Bar Chart
                    const ancCtx = document.getElementById('ancChart');
                    if (ancCtx && window.Chart) {
                        // Double-check and destroy any existing chart on this canvas
                        const existingAncChart = Chart.getChart(ancCtx);
                        if (existingAncChart) {
                            existingAncChart.destroy();
                        }
                        
                        const ancData = latestStateDetails.anc_visits || { anc1: 0, anc2: 0, anc3: 0, anc4: 0 };
                        console.log('ANC Data for charts:', ancData);
                        const ancValues = [
                            parseInt(ancData.anc1) || 0,
                            parseInt(ancData.anc2) || 0,
                            parseInt(ancData.anc3) || 0,
                            parseInt(ancData.anc4) || 0
                        ];
                        console.log('ANC Values for chart:', ancValues);
                        
                        ancChart = new Chart(ancCtx, {
                            type: 'bar',
                            data: {
                                labels: ['ANC 1', 'ANC 2', 'ANC 3', 'ANC 4'],
                                datasets: [{
                                    label: 'Visits',
                                    data: ancValues,
                                    backgroundColor: [
                                        'rgba(59, 130, 246, 0.9)',
                                        'rgba(59, 130, 246, 0.9)',
                                        'rgba(59, 130, 246, 0.9)',
                                        'rgba(59, 130, 246, 0.9)'
                                    ],
                                    borderColor: 'rgb(59, 130, 246)',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: { display: false },
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        color: '#1f2937',
                                        font: {
                                            weight: 'bold',
                                            size: 14
                                        },
                                        formatter: (value) => value.toLocaleString()
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        padding: 12,
                                        titleFont: { size: 14, weight: 'bold' },
                                        bodyFont: { size: 13 },
                                        callbacks: {
                                            label: function(context) {
                                                return 'Visits: ' + context.parsed.y.toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1,
                                            font: { size: 12 },
                                            color: '#6b7280'
                                        },
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            font: { size: 12, weight: 'bold' },
                                            color: '#374151'
                                        },
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            },
                        });
                    }

                    // Services Bar Chart
                    const servicesCtx = document.getElementById('servicesChart');
                    if (servicesCtx && window.Chart) {
                        // Double-check and destroy any existing chart on this canvas
                        const existingServicesChart = Chart.getChart(servicesCtx);
                        if (existingServicesChart) {
                            existingServicesChart.destroy();
                        }
                        
                        const servicesData = latestStateDetails.services || { skilled_delivery: 0, post_natal: 0, immunization: 0 };
                        console.log('Services Data:', servicesData);
                        const serviceValues = [
                            parseInt(servicesData.skilled_delivery) || 0,
                            parseInt(servicesData.post_natal) || 0,
                            parseInt(servicesData.immunization) || 0
                        ];
                        console.log('Service Values:', serviceValues);
                        
                        servicesChart = new Chart(servicesCtx, {
                            type: 'bar',
                            data: {
                                labels: ['Skilled Delivery', 'Post Natal Care', 'Immunization'],
                                datasets: [{
                                    label: 'Services',
                                    data: serviceValues,
                                    backgroundColor: [
                                        'rgba(16, 185, 129, 0.9)',
                                        'rgba(16, 185, 129, 0.9)',
                                        'rgba(16, 185, 129, 0.9)'
                                    ],
                                    borderColor: 'rgb(16, 185, 129)',
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true,
                                plugins: {
                                    legend: { display: false },
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        color: '#1f2937',
                                        font: {
                                            weight: 'bold',
                                            size: 14
                                        },
                                        formatter: (value) => value.toLocaleString()
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        padding: 12,
                                        titleFont: { size: 14, weight: 'bold' },
                                        bodyFont: { size: 13 },
                                        callbacks: {
                                            label: function(context) {
                                                return 'Count: ' + context.parsed.y.toLocaleString();
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1,
                                            font: { size: 12 },
                                            color: '#6b7280'
                                        },
                                        grid: {
                                            color: 'rgba(0, 0, 0, 0.05)'
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            font: { size: 12, weight: 'bold' },
                                            color: '#374151'
                                        },
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            },
                        });
                    }

                }, 100);
            }

            // Initialize Growth Chart
            function initializeGrowthChart() {
                const growthCtx = document.getElementById('growthChart');
                if (growthCtx && window.Chart && growthChartData && growthChartData.length > 0) {
                    // Destroy existing chart if any
                    const existingChart = Chart.getChart(growthCtx);
                    if (existingChart) {
                        existingChart.destroy();
                    }

                    // Prepare data - use labels and percentages
                    const labels = growthChartData.map(item => item.label);
                    const percentages = growthChartData.map(item => item.percentage);
                    const beneficiaryCounts = growthChartData.map(item => item.total_beneficiaries);
                    
                    // Generate vibrant colors for each bar
                    const colors = growthChartData.map((item, index) => {
                        const hue = (index * 120) % 360; // Different colors for each year
                        return `hsla(${hue}, 70%, 55%, 1)`;
                    });

                    growthChart = new Chart(growthCtx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Percentage (%)',
                                data: percentages,
                                backgroundColor: colors,
                                borderColor: colors.map(c => c.replace('55%', '45%')),
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        font: { size: 12, weight: 'bold' },
                                        color: '#374151',
                                        padding: 15
                                    }
                                },
                                datalabels: {
                                    display: true,
                                    color: '#1f2937',
                                    font: {
                                        weight: 'bold',
                                        size: 14
                                    },
                                    formatter: (value, context) => {
                                        return value.toFixed(1) + '%';
                                    },
                                    anchor: 'end',
                                    align: 'top'
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    titleFont: { size: 14, weight: 'bold' },
                                    bodyFont: { size: 13 },
                                    callbacks: {
                                        label: function(context) {
                                            const index = context.dataIndex;
                                            const count = beneficiaryCounts[index];
                                            return [
                                                'Percentage: ' + context.parsed.y.toFixed(1) + '%',
                                                'Total Beneficiaries: ' + count.toLocaleString()
                                            ];
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    ticks: {
                                        font: { size: 12 },
                                        color: '#6b7280',
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Percentage (%)',
                                        font: { size: 12, weight: 'bold' },
                                        color: '#374151'
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: { size: 12, weight: 'bold' },
                                        color: '#374151'
                                    },
                                    grid: {
                                        display: false
                                    },
                                    title: {
                                        display: true,
                                        text: 'Year',
                                        font: { size: 12, weight: 'bold' },
                                        color: '#374151'
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Make functions globally available for Alpine.js
            window.initializeCharts = initializeCharts;
            window.destroyCharts = destroyCharts;
            window.initializeStatesPieChart = initializeStatesPieChart;
            window.initializeStatesBarChart = initializeStatesBarChart;
            window.initializeGrowthChart = initializeGrowthChart;

            // Initialize Zones Pie Chart (Beneficiaries by Zone)
            function initializeStatesPieChart() {
                const pieCtx = document.getElementById('statesPieChart');
                if (pieCtx && window.Chart && zoneChartData && zoneChartData.length > 0) {
                    // Destroy existing chart if any
                    const existingChart = Chart.getChart(pieCtx);
                    if (existingChart) {
                        existingChart.destroy();
                    }

                    // Prepare data from zones
                    const zoneNames = zoneChartData.map(zone => zone.name);
                    const beneficiaryCounts = zoneChartData.map(zone => zone.total_beneficiaries);
                    
                    // Generate vibrant, visible colors for each zone
                    const colors = zoneChartData.map((zone, index) => {
                        const hue = (index * 137.508) % 360; // Golden angle for color distribution
                        return `hsla(${hue}, 80%, 55%, 1)`; // More saturated, brighter, fully opaque
                    });

                    statesPieChart = new Chart(pieCtx, {
                        type: 'pie',
                        data: {
                            labels: zoneNames,
                            datasets: [{
                                label: 'Total Beneficiaries',
                                data: beneficiaryCounts,
                                backgroundColor: colors,
                                borderColor: '#ffffff',
                                borderWidth: 3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'right',
                                    labels: {
                                        font: { size: 11 },
                                        color: '#374151',
                                        padding: 10,
                                        usePointStyle: true
                                    }
                                },
                                datalabels: {
                                    display: true,
                                    color: '#1f2937',
                                    font: {
                                        weight: 'bold',
                                        size: 12
                                    },
                                    formatter: (value, context) => {
                                        return value.toLocaleString();
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    titleFont: { size: 14, weight: 'bold' },
                                    bodyFont: { size: 13 },
                                    callbacks: {
                                        label: function(context) {
                                            const zoneData = zoneChartData[context.dataIndex];
                                            const total = beneficiaryCounts.reduce((a, b) => a + b, 0);
                                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                                            return [
                                                context.label + ': ' + context.parsed.toLocaleString(),
                                                'Percentage: ' + percentage + '%',
                                                'Active: ' + (zoneData.active_beneficiaries || 0).toLocaleString()
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Initialize States Bar Chart (History)
            function initializeStatesBarChart() {
                const barCtx = document.getElementById('statesBarChart');
                if (barCtx && window.Chart && stateChartData && stateChartData.length > 0) {
                    // Destroy existing chart if any
                    const existingChart = Chart.getChart(barCtx);
                    if (existingChart) {
                        existingChart.destroy();
                    }

                    // Prepare data
                    const stateNames = stateChartData.map(state => state.name);
                    const beneficiaryCounts = stateChartData.map(state => state.total_beneficiaries);
                    
                    // Generate vibrant, visible colors for each state
                    const colors = stateChartData.map((state, index) => {
                        const hue = (index * 137.508) % 360; // Golden angle for color distribution
                        return `hsla(${hue}, 80%, 55%, 1)`; // More saturated, brighter, fully opaque
                    });

                    statesBarChart = new Chart(barCtx, {
                        type: 'bar',
                        data: {
                            labels: stateNames,
                            datasets: [{
                                label: 'Total Beneficiaries',
                                data: beneficiaryCounts,
                                backgroundColor: colors,
                                borderColor: colors.map(c => c.replace('55%', '45%')), // Darker border
                                borderWidth: 2
                            }]
                        },
                        options: {
                            indexAxis: 'y', // Horizontal bar chart
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                datalabels: {
                                    display: true,
                                    color: '#1f2937',
                                    font: {
                                        weight: 'bold',
                                        size: 11
                                    },
                                    anchor: 'end',
                                    align: 'right',
                                    formatter: (value) => {
                                        return value.toLocaleString();
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    titleFont: { size: 14, weight: 'bold' },
                                    bodyFont: { size: 13 },
                                    callbacks: {
                                        label: function(context) {
                                            const stateData = stateChartData[context.dataIndex];
                                            return [
                                                'Total Beneficiaries: ' + context.parsed.x.toLocaleString(),
                                                'Active: ' + (stateData.active_beneficiaries || 0).toLocaleString(),
                                                'PHCs: ' + (stateData.phc_count || 0).toLocaleString()
                                            ];
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: {
                                        font: { size: 11 },
                                        color: '#6b7280',
                                        callback: function(value) {
                                            return value.toLocaleString();
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Number of Beneficiaries',
                                        font: { size: 12, weight: 'bold' },
                                        color: '#374151'
                                    }
                                },
                                y: {
                                    ticks: {
                                        font: { size: 10 },
                                        color: '#374151'
                                    },
                                    grid: {
                                        display: false
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Initialize states charts when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        initializeGrowthChart();
                        initializeStatesPieChart();
                        initializeStatesBarChart();
                    }, 300);
                });
            } else {
                setTimeout(() => {
                    initializeGrowthChart();
                    initializeStatesPieChart();
                    initializeStatesBarChart();
                }, 300);
            }
        </script>
    </div>
