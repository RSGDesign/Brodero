@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 30px;">
    <h2>Admin Dashboard</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
        <a href="{{ route('admin.products.index') }}" style="background: white; padding: 30px; border-radius: 8px; text-align: center; text-decoration: none; color: #003366; border: 2px solid #003366;">
            <h3>Produse</h3>
            <p>Gestionează produsele</p>
        </a>
        
        <a href="{{ route('admin.categories.index') }}" style="background: white; padding: 30px; border-radius: 8px; text-align: center; text-decoration: none; color: #003366; border: 2px solid #003366;">
            <h3>Categorii</h3>
            <p>Gestionează categoriile</p>
        </a>
        
        <a href="{{ route('admin.orders.index') }}" style="background: white; padding: 30px; border-radius: 8px; text-align: center; text-decoration: none; color: #003366; border: 2px solid #003366;">
            <h3>Comenzi</h3>
            <p>Vezi și gestionează comenzile</p>
        </a>
        
        <a href="{{ route('admin.coupons.index') }}" style="background: white; padding: 30px; border-radius: 8px; text-align: center; text-decoration: none; color: #003366; border: 2px solid #003366;">
            <h3>Cupoane</h3>
            <p>Gestionează cupoanele de reducere</p>
        </a>
    </div>
</div>
@endsection
