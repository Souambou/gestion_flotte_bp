<?php

namespace App\Http\Controllers;

use App\Exports\RapportExport;
use App\Models\Checklist;
use App\Models\Maintenance;
use App\Models\Reservation;
use App\Models\Vehicule;
use App\Services\ServiceKpi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Section Rapports : les indicateurs sont calcules par le serveur a partir
 * des donnees enregistrees. Aucun utilisateur ne saisit de rapport.
 */
class RapportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:rapports.consulter');
        $this->middleware('permission:rapports.exporter')->only('exporter');
    }

    public function index(Request $request)
    {
        $kpi = $this->kpi($request);

        return view('rapports.index', [
            'kpi' => $kpi,
            'synthese' => $kpi->synthese(),
            'evolution' => $kpi->evolutionReservations(),
            'repartition' => $kpi->repartitionStatuts(),
            'vehicules' => $kpi->performanceVehicules(),
            'chauffeurs' => $kpi->performanceChauffeurs(),
        ]);
    }

    /** Historique complet des reservations (section dediee du cahier des charges). */
    public function historique(Request $request)
    {
        $kpi = $this->kpi($request);

        return view('rapports.historique', [
            'kpi' => $kpi,
            'reservations' => Reservation::with(['demandeur', 'vehicule', 'chauffeur', 'deplacement'])
                ->whereBetween('date_debut', [$kpi->debut(), $kpi->fin()])
                ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
                ->latest('date_debut')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function occupation(Request $request)
    {
        $kpi = $this->kpi($request);

        return view('rapports.occupation', [
            'kpi' => $kpi,
            'vehicules' => $kpi->performanceVehicules(),
            'tauxGlobal' => $kpi->tauxOccupationFlotte(),
        ]);
    }

    public function checklists(Request $request)
    {
        $kpi = $this->kpi($request);

        return view('rapports.checklists', [
            'kpi' => $kpi,
            'checklists' => Checklist::with(['vehicule', 'auteur'])
                ->whereBetween('date_controle', [$kpi->debut(), $kpi->fin()])
                ->latest('date_controle')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    /** Demandes par service, avec taux de satisfaction (suivi mensuel). */
    public function departements(Request $request)
    {
        $kpi = $this->kpi($request);
        $lignes = $kpi->demandesParDepartement();

        return view('rapports.departements', [
            'kpi' => $kpi,
            'lignes' => $lignes,
            'total' => $kpi->totalDemandesParDepartement($lignes),
        ]);
    }

    /** Justificatif de consommation, vehicule par vehicule. */
    public function kilometrage(Request $request)
    {
        $kpi = $this->kpi($request);

        $vehicules = Vehicule::orderBy('immatriculation')->get();
        $vehicule = $request->filled('vehicule')
            ? $vehicules->firstWhere('id', (int) $request->input('vehicule'))
            : $vehicules->first();

        return view('rapports.kilometrage', [
            'kpi' => $kpi,
            'vehicules' => $vehicules,
            'vehicule' => $vehicule,
            'lignes' => $vehicule ? $kpi->consommationVehicule($vehicule) : collect(),
            'performances' => $kpi->performanceVehicules(),
        ]);
    }

    public function couts(Request $request)
    {
        $kpi = $this->kpi($request);

        return view('rapports.couts', [
            'kpi' => $kpi,
            'vehicules' => $kpi->performanceVehicules(),
            'maintenances' => Maintenance::with('vehicule')
                ->whereBetween('date_realisee', [$kpi->debut(), $kpi->fin()])
                ->orderByDesc('cout')
                ->get(),
            'coutDeplacements' => $kpi->coutTotal(),
        ]);
    }

    /**
     * Exports multiples : PDF, Excel (xlsx) et CSV.
     * $rapport : synthese | historique | occupation | chauffeurs | couts
     */
    public function exporter(Request $request, string $rapport, string $format)
    {
        abort_unless(in_array($rapport, ['synthese', 'historique', 'occupation', 'chauffeurs', 'couts', 'departements', 'kilometrage']), 404);
        abort_unless(in_array($format, ['pdf', 'xlsx', 'csv']), 404);

        $kpi = $this->kpi($request);
        [$titre, $entetes, $lignes] = $this->donneesRapport($rapport, $kpi, $request);

        $nomFichier = 'benin-petro-'.$rapport.'-'.$kpi->debut()->format('Ymd').'-'.$kpi->fin()->format('Ymd');

        if ($format === 'csv') {
            return $this->csv($nomFichier.'.csv', $entetes, $lignes);
        }

        if ($format === 'xlsx') {
            return Excel::download(new RapportExport($lignes, $entetes, $titre), $nomFichier.'.xlsx');
        }

        return Pdf::loadView('rapports.pdf.tableau', [
            'titre' => $titre,
            'entetes' => $entetes,
            'lignes' => $lignes,
            'kpi' => $kpi,
            'synthese' => $kpi->synthese(),
            'genereLe' => now(),
            'logo' => $this->logoEncode(),
        ])->setPaper('a4', count($entetes) > 6 ? 'landscape' : 'portrait')
            ->download($nomFichier.'.pdf');
    }

    /**
     * Le logo est encode en base64 : dompdf n'a ainsi aucun chemin de fichier
     * a resoudre, ce qui evite un en-tete vide selon l'hebergement.
     */
    protected function logoEncode(): ?string
    {
        $chemin = public_path('images/logo-benin-petro-blanc.png');

        if (! is_file($chemin)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($chemin));
    }

    protected function csv(string $nom, array $entetes, array $lignes): StreamedResponse
    {
        return response()->streamDownload(function () use ($entetes, $lignes) {
            $sortie = fopen('php://output', 'w');
            fprintf($sortie, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 pour Excel
            fputcsv($sortie, $entetes, ';');
            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }
            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function donneesRapport(string $rapport, ServiceKpi $kpi, Request $request): array
    {
        return match ($rapport) {
            'historique' => [
                'Historique des réservations',
                ['Code', 'Demandeur', 'Véhicule', 'Chauffeur', 'Départ', 'Retour', 'Trajet', 'Statut', 'Km parcourus'],
                Reservation::with(['demandeur', 'vehicule', 'chauffeur', 'deplacement'])
                    ->whereBetween('date_debut', [$kpi->debut(), $kpi->fin()])
                    ->latest('date_debut')
                    ->get()
                    ->map(fn (Reservation $r) => [
                        $r->code,
                        $r->demandeur?->nom_complet,
                        $r->vehicule?->immatriculation ?? '—',
                        $r->chauffeur?->nom_complet ?? 'Sans chauffeur',
                        $r->date_debut->format('d/m/Y H:i'),
                        $r->date_fin->format('d/m/Y H:i'),
                        $r->trajet,
                        $r->statut_libelle,
                        $r->deplacement?->distance_parcourue ?? '—',
                    ])->all(),
            ],
            'occupation' => [
                'Taux d\'occupation de la flotte',
                ['Immatriculation', 'Véhicule', 'Type', 'Réservations', 'Déplacements', 'Kilomètres', 'Taux d\'occupation (%)'],
                $kpi->performanceVehicules()->map(fn ($l) => [
                    $l['vehicule']->immatriculation,
                    $l['vehicule']->marque.' '.$l['vehicule']->modele,
                    $l['vehicule']->type_libelle,
                    $l['reservations'],
                    $l['deplacements'],
                    $l['kilometres'],
                    $l['taux_occupation'],
                ])->all(),
            ],
            'chauffeurs' => [
                'Activité des chauffeurs',
                ['Matricule', 'Chauffeur', 'Déplacements', 'Kilomètres', 'Heures de conduite'],
                $kpi->performanceChauffeurs()->map(fn ($l) => [
                    $l['chauffeur']->matricule,
                    $l['chauffeur']->nom_complet,
                    $l['deplacements'],
                    $l['kilometres'],
                    $l['heures'],
                ])->all(),
            ],
            'departements' => [
                'Demandes par service',
                ['Service', 'Nombre de demandes', 'Satisfaites', 'Non satisfaites', 'Taux de satisfaction (%)', 'Taux de non satisfaction (%)'],
                (function () use ($kpi) {
                    $lignes = $kpi->demandesParDepartement();
                    $total = $kpi->totalDemandesParDepartement($lignes);

                    // Le sigle suffit en export : les colonnes y sont nombreuses.
                    $donnees = $lignes->map(fn ($l) => [
                        $l['sigle'],
                        $l['demandes'],
                        $l['satisfaites'],
                        $l['non_satisfaites'],
                        $l['taux_satisfaction'],
                        $l['taux_non_satisfaction'],
                    ])->all();

                    $donnees[] = [
                        'TOTAL',
                        $total['demandes'],
                        $total['satisfaites'],
                        $total['non_satisfaites'],
                        $total['taux_satisfaction'],
                        $total['taux_non_satisfaction'],
                    ];

                    return $donnees;
                })(),
            ],
            'kilometrage' => [
                'Justificatif de consommation par véhicule',
                ['Index', 'Date', 'Motif du déplacement', 'Km au début', 'Km à la fin', 'Km parcouru', 'Consommation aux 100 km', 'Débit (litres)', 'Solde (litres)', 'Coût carburant (FCFA)'],
                (function () use ($kpi, $request) {
                    $vehicule = $request->filled('vehicule')
                        ? Vehicule::find((int) $request->input('vehicule'))
                        : Vehicule::orderBy('immatriculation')->first();

                    if (! $vehicule) {
                        return [];
                    }

                    return $kpi->consommationVehicule($vehicule)->map(fn ($l) => [
                        $l['index'],
                        $l['date']?->format('d/m/Y'),
                        $l['motif'],
                        $l['km_debut'],
                        $l['km_fin'] ?? '—',
                        $l['km_parcouru'] ?? '—',
                        $l['consommation_100km'] ?? '—',
                        $l['debit_litres'] ?? '—',
                        $l['solde_litres'],
                        round($l['cout']),
                    ])->all();
                })(),
            ],
            'couts' => [
                'Coûts d\'exploitation',
                ['Immatriculation', 'Véhicule', 'Déplacements', 'Kilomètres', 'Coût déplacements (FCFA)', 'Coût maintenance (FCFA)'],
                $kpi->performanceVehicules()->map(function ($l) use ($kpi) {
                    $maintenance = Maintenance::where('vehicule_id', $l['vehicule']->id)
                        ->whereBetween('date_realisee', [$kpi->debut(), $kpi->fin()])
                        ->sum('cout');

                    return [
                        $l['vehicule']->immatriculation,
                        $l['vehicule']->marque.' '.$l['vehicule']->modele,
                        $l['deplacements'],
                        $l['kilometres'],
                        round($l['cout']),
                        round((float) $maintenance),
                    ];
                })->all(),
            ],
            default => [
                'Synthèse d\'activité',
                ['Indicateur', 'Valeur'],
                collect($kpi->synthese())->map(fn ($valeur, $cle) => [
                    ucfirst(str_replace('_', ' ', $cle)),
                    is_float($valeur) ? number_format($valeur, 1, ',', ' ') : $valeur,
                ])->values()->all(),
            ],
        };
    }

    protected function kpi(Request $request): ServiceKpi
    {
        return ServiceKpi::periode($request->input('debut'), $request->input('fin'));
    }
}
