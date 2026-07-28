<h1>{{ $title }}</h1>
@if($visible)
    @foreach($items as $item)
        <span>{{ $item }}</span>
    @endforeach
@endif
