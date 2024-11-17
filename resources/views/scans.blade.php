{{-- PAGE NOS SCANS --}}


{{-- Fonction de recherche  --}}
<form action="{{ route('library.searchManga') }}" method="GET">
    {{-- Respecter les attributs du champs input surtout les attributs 'name' et 'value' --}}
    <input 
        type="text" 
        name="q" 
        placeholder="Rechercher par titre, genre, tag ou langue..."
        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:border-primary"
        value="{{ request('q') }}"
    >
</form>

    {{-- Mangas Populaires Section --}}


    {{-- une boucle pour afficher les contenues mangas existants --}}

    @foreach($popularManga as $manga)

        {{-- pour la récupération de l'image de couverture du manga --}}

        <img src="{{ Storage::url($manga->thumbnail_path) }}" alt="{{ $manga->title }}">

        {{-- Les informations sur le manga  --}}

            {{ $manga->title }} {{-- Titre du manga --}}
            {{ Str::limit($manga->description, 100) }} {{-- afficher la description du manga en limitant le nombre de caractère de sorte qu'il y qit des points de suspension lorsque la limite sera atteint // Tu pourras l'ajuster au besoin --}}
            {{ $manga->language }} {{-- récupération de la langue utilisée pour ce manga --}}
            {{ $manga->views_count }} {{-- récupérer le nombre de vues --}}
            {{ $manga->likes_count }} {{-- Récupérer le nombre de likes --}}
            {{ $manga->age_rating }} {{-- Récupérer la notation --}}

        {{-- Route pour Avoir les détails sur le manga  --}}

        <a href="{{ route('manga.show', $manga->slug) }}" class="btn-read">Commencer la lecture</a>
    @endforeach