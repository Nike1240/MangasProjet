{{-- Détails sur le contenu  --}}

    {{ $content->title }}

    <img src="{{ Storage::url($content->cover_image) }}" alt="{{ $content->title }}" >

    {{ $content->author->name }}

    {{-- Genres --}}

    @foreach($content->genres as $genre)
        <span class="badge bg-primary me-1">{{ $genre->name }}</span>
    @endforeach

    {{-- Description --}}
        {{ $content->description }}  
        
    {{-- Nombre de chapitre --}}
    Chapitres ({{ $content->chapters->count() }} au total)

    {{ $content->language }}  

    @if($content->age_rating)
        {{ $content->age_rating }}
    @endif
{{--end--}}

@if($content->type === 'manga')
    {{-- MODAL POUR LES MANGAS --}}

        {{-- Auteur --}}

        {{ $content->author->name }}


        {{-- Liste des chapitres --}}

        @foreach($content->chapters->sortBy('number') as $chapter)
            Chapitre {{ $chapter->number }}
            {{ $chapter->title }}
            {{ $chapter->description }}
            {{ $chapter->pages->count() }} page(s)

            @if($chapter->pages->first())
                <img src="{{ Storage::url($chapter->pages->first()->thumbnail_path) }}" alt="Première page" >
            @else
                Pas d'image
            @endif

        @endforeach

        {{-- statistiques du manga  --}}

        {{ $content->language }}
        {{ number_format($content->views_count) }} vues 
        {{ number_format($content->likes()->count()) }} likes
        {{ number_format($content->comments()->count()) }} commentaires


    {{--end MODAL POUR LES MANGAS --}}

@else

    {{-- MODAL POUR LES ANIMÉS --}}

        {{-- Auteur --}}

        {{ $content->author->name }}

        {{-- Liste des saisons --}}

        @foreach($content->seasons->sortBy('number') as $season)
            Saison {{ $season->number }}
            {{ $season->title }}
            {{ $season->description }}
            {{ $season->episodes_count }} episode(s)

            @if($season->episode->first())
                <img src="{{ Storage::url($season->episode->first()->thumbnail_path) }}" alt="Première épisode" >
            @else
                Pas d'image
            @endif

        @endforeach

        {{ $content->language }}
        {{ number_format($content->views_count) }} vues 
        {{ number_format($content->likes()->count()) }} likes
        {{ number_format($content->comments()->count()) }} commentaires

    {{--end MODAL POUR LES ANIMÉS --}}

@endif

{{-- MODAL POUR LES ANIMÉS-- Récupération de toutes les épisodes --}}


    @foreach($content->seasons as $season)
        Saison {{ $season->number }}
        
        @foreach($season->episodes as $episode)
                    <span class="font-medium">Épisode {{ $episode->episode_number }}</span>
                    <span class="text-sm text-gray-500">{{ $episode->duration }} min</span>
                </div>
                @if($episode->title)
                    {{ $episode->title }}</p>
                @endif
        @endforeach
    @endforeach

{{-- end  --}}


{{-- Section commentaires --}}

{{-- Liste des commentaires --}}
@foreach($content->comments()->latest()->get() as $comment)
    <div class="border-b last:border-b-0 pb-4">
        <div class="flex justify-between items-center mb-2">
            <span class="font-medium">{{ $comment->user->name }}</span>
            <span class="text-sm text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
        </div>
        <p class="text-gray-600">{{ $comment->body }}</p>
    </div>
@endforeach
