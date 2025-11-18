@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Categorii</h2>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-success">Adaugă categorie</a>
    </div>
    
    <table style="width: 100%; background: white; border-collapse: collapse;">
        <thead style="background: #f8f9fa;">
            <tr>
                <th style="padding: 12px; text-align: left;">ID</th>
                <th style="padding: 12px; text-align: left;">Nume</th>
                <th style="padding: 12px; text-align: left;">Slug</th>
                <th style="padding: 12px; text-align: center;">Produse</th>
                <th style="padding: 12px; text-align: right;">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">{{ $category->id }}</td>
                    <td style="padding: 12px;"><strong>{{ $category->name }}</strong></td>
                    <td style="padding: 12px;">{{ $category->slug }}</td>
                    <td style="padding: 12px; text-align: center;">{{ $category->products_count }}</td>
                    <td style="padding: 12px; text-align: right;">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Sigur ștergi?')">Șterge</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center;">Nu există categorii.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div style="margin-top: 20px;">
        {{ $categories->links() }}
    </div>
</div>
@endsection
