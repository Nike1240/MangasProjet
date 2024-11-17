{{-- PAGE NOS Animés --}}


{{-- Fonction de recherche  --}}
<form action="{{ route('library.searchAnime') }}" method="GET">
    {{-- Respecter les attributs du champs input surtout les attributs 'name' et 'value' --}}
    <input 
        type="text" 
        name="q" 
        placeholder="Rechercher par titre, genre, tag ou langue..."
        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:border-primary"
        value="{{ request('q') }}"
    >
</form>

{{-- Animes Populaires Section --}}

{{-- une boucle pour afficher les contenues animés existants --}}

    @foreach($popularAnime as $anime)

        {{-- pour la récupération de l'image de couverture de l'animé --}}

        <img src="{{ Storage::url($anime->thumbnail_path) }}" alt="{{ $anime->title }}">

        {{-- Les informations sur le animés  --}}

        {{ $anime->title }} {{-- Titre du manga --}}
        {{ Str::limit($anime->description, 100) }} {{-- afficher la description de l'animé en limitant le nombre de caractère de sorte qu'il y qit des points de suspension lorsque la limite sera atteint // Tu pourras l'ajuster au besoin --}}
        {{ $manga->views_count }} {{-- récupérer le nombre de vues --}}
        {{ $manga->likes_count }} {{-- Récupérer le nombre de likes --}}
        {{ $anime->age_rating }} {{-- Récupérer la notation --}}

        {{-- Route pour Avoir les détails sur l'animé  --}}

        <a href="{{ route('anime.show', $anime->slug) }}" class="btn-watch">Regarder</a>
    @endforeach