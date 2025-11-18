@extends('layouts.app')

@section('content')
<div style="max-width: 700px; margin: 0 auto; padding: 30px;">
    <h2>{{ isset($category) ? 'Editează categorie' : 'Adaugă categorie' }}</h2>
    
    <form method="POST" action="{{ isset($category) ? route('admin.categories.update', $category) : route('admin.categories.store') }}" style="background: white; padding: 30px; border-radius: 8px; margin-top: 20px;">
        @csrf
        @if(isset($category))
            @method('PATCH')
        @endif
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Nume *</strong></label>
            <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Slug</strong></label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug ?? '') }}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <small>Lasă gol pentru generare automată</small>
        </div>
        
        <button type="submit" class="btn btn-success">{{ isset($category) ? 'Actualizează' : 'Salvează' }}</button>
        <a href="{{ route('admin.categories.index') }}" class="btn">Anulează</a>
    </form>
</div>
@endsection
