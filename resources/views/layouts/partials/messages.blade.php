{{--
    Les messages du serveur sont remis a SweetAlert2 plutot qu'affiches en
    bandeau : le retour est immediat, visible, et identique dans toute
    l'application. Le bloc <noscript> garantit que l'information reste
    lisible si JavaScript est indisponible.
--}}
@php
    $succes = session('succes');
    $erreur = session('erreur');
    $info = session('info');
    $motDePasse = session('mot_de_passe_provisoire');
    $erreursValidation = $errors->all();
@endphp

@if($succes || $erreur || $info || $motDePasse || $erreursValidation)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                @if($succes && $motDePasse)
                    Swal.fire({
                        icon: 'success',
                        title: @json($succes),
                        html: "Mot de passe provisoire à transmettre :"
                            + "<div style='margin-top:.6rem;font-family:monospace;font-size:1.05rem;"
                            + "font-weight:700;background:#F2F8F4;border:1px solid #DBEAE1;"
                            + "border-radius:.5rem;padding:.6rem'>" + @json($motDePasse) + "</div>",
                        confirmButtonText: 'J\'ai noté',
                        confirmButtonColor: '#01582D',
                    });
                @elseif($succes)
                    notifier.succes(@json($succes));
                @endif

                @if($erreur)
                    notifier.erreur(@json($erreur));
                @endif

                @if($info)
                    notifier.info(@json($info));
                @endif

                @if($erreursValidation)
                    notifier.validation(@json($erreursValidation));
                @endif
            });
        </script>
    @endpush

    <noscript>
        @if($succes)
            <div class="mb-5 rounded-xl2 border border-petro-200 bg-petro-50 px-4 py-3 text-sm text-petro-800">
                {{ $succes }}
                @if($motDePasse)
                    <span class="ml-1 font-mono font-bold">{{ $motDePasse }}</span>
                @endif
            </div>
        @endif

        @if($erreur)
            <div class="mb-5 rounded-xl2 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $erreur }}</div>
        @endif

        @if($info)
            <div class="mb-5 rounded-xl2 border border-ardoise-200 bg-white px-4 py-3 text-sm text-ardoise-700">{{ $info }}</div>
        @endif

        @if($erreursValidation)
            <div class="mb-5 rounded-xl2 border border-red-200 bg-red-50 px-4 py-3">
                <p class="text-sm font-semibold text-red-800">Corrigez les points suivants :</p>
                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                    @foreach($erreursValidation as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif
    </noscript>
@endif
