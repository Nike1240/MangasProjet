{{-- PAGE D'ACCUEIL --}}

    {{-- Mangas Populaires Section --}}

            <a href="{{ route('contents.index', ['type' => 'manga']) }}" class="voir-plus">Voir plus de manga></a>

            {{-- une boucle pour afficher les contenues mangas existants --}}

            @foreach($popularManga as $manga)

                {{-- pour la récupération de l'image de couverture du manga --}}

                <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}">

                {{-- Les informations sur le manga  --}}

                    {{ $manga->title }} {{-- Titre du manga --}}
                    {{ Str::limit($manga->description, 100) }} {{-- afficher la description du manga en limitant le nombre de caractère de sorte qu'il y qit des points de suspension lorsque la limite sera atteint // Tu pourras l'ajuster au besoin --}}
                    {{ $manga->language }} {{-- récupération de la langue utilisée pour ce manga --}}
                    {{ $manga->views_count }} {{-- récupérer le nombre de vues --}}
                    {{ $manga->likes_count }} {{-- Récupérer le nombre de likes --}}
                    {{ $manga->age_rating }} {{-- Récupérer la notation --}}

                {{-- Route pour Avoir les détails sur le manga  --}}

                <a href="#" class="btn-read">Commencer la lecture</a>
            @endforeach

    {{-- Animes Populaires Section --}}

            <a href="#" class="voir-plus">Voir d'animés ></a>

            {{-- une boucle pour afficher les contenues animés existants --}}

            @foreach($popularAnime as $anime)

                {{-- pour la récupération de l'image de couverture de l'animé --}}

                <img src="{{ Storage::url($anime->cover_image) }}" alt="{{ $anime->title }}">

                {{-- Les informations sur le animés  --}}

                {{ $anime->title }} {{-- Titre du manga --}}
                {{ Str::limit($anime->description, 100) }} {{-- afficher la description de l'animé en limitant le nombre de caractère de sorte qu'il y qit des points de suspension lorsque la limite sera atteint // Tu pourras l'ajuster au besoin --}}
                {{ $manga->views_count }} {{-- récupérer le nombre de vues --}}
                {{ $manga->likes_count }} {{-- Récupérer le nombre de likes --}}
                {{ $anime->age_rating }} {{-- Récupérer la notation --}}

                {{-- Route pour Avoir les détails sur l'animé  --}}

                <a href="#" class="btn-watch">Regarder</a>
            @endforeach

