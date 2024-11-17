{{-- PAGE DE LA LIBRAIRIE  --}}

{{-- Fonction de recherche  --}}
<form action="{{ route('library.search') }}" method="GET">
    {{-- Respecter les attributs du champs input surtout les attributs 'name' et 'value' --}}
    <input 
        type="text" 
        name="q" 
        placeholder="Rechercher par titre, genre, tag ou langue..."
        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:border-primary"
        value="{{ request('q') }}"
    >
</form>

{{-- Une boucle pour afficher les genres  // classifications par catégorie --}}

    @foreach ($genres as $category)
        <li>
            <div class="category">
                <span>
                    <a href="{{ route('categories.show', ['genre' => $category->id]) }}" class="btn default">{{ $category->name }}</a>
                </span>
            
            </div>
            
        </li>
    @endforeach
    <a href="{{ route('contents.index') }}" class="btn {{ !request('language') ? 'active' : '' }}">Tous</a>
    
    <a href="{{ route('contents.index', ['language' => 'fr']) }}" 
        class="btn {{ request('language') === 'fr' ? 'active' : '' }}">Version Française</a>

    <a href="{{ route('contents.index', ['language' => 'en']) }}" 
        class="btn {{ request('language') === 'en' ? 'active' : '' }}">Version Anglaise</a>

{{-- Fin des catégories --}}


{{-- NOS SCANS --}}


        <a href="{{ route('contents.index', ['type' => 'manga']) }}" class="voir-plus">Voir plus de manga></a>

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



{{-- NOS ANIMÉS --}}



        <a href="{{ route('contents.index', ['type' => 'anime']) }}" class="voir-plus">Voir d'animés ></a>

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


            {{-- Pagination --}}
            <div class="pagination">
                {{ $contents->links() }}
            </div>





{{-- @foreach($contents as $content)
    <div class="content-card">
        <img src="{{ Storage::url($content->cover_image) }}" alt="{{ $content->title }}">
        <div class="content-info">
            <h3>{{ $content->title }}</h3>
            <p>{{ Str::limit($content->description, 100) }}</p>
            <div class="content-meta">
                @if($content->type === 'manga')
                    <span class="chapter">Ch. {{ $content->chapters_count }}</span>
                @else
                    <span class="episode">Ep. {{ $content->episodes_count }}</span>
                @endif
                <span class="rating">{{ $content->age_rating }}+</span>
            </div>
            <div class="action-buttons">
                <a href="{{ route($content->type.'.show', $content->slug) }}" 
                    class="btn-primary">
                    {{ $content->type === 'manga' ? 'Commencer la lecture' : 'Regarder' }}
                </a>
                <button class="btn-bookmark">Marquer</button>
            </div>
        </div>
    </div>
@endforeach --}}




