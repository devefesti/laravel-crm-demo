@extends('admin::layouts.master')

@section('page_title')
    {{ __('Modifica influencer') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        <h1>
            {{ Breadcrumbs::render('influencers') }}
        
            {{ __('Influencers') }}
        </h1>

        <form action="{{ route('admin.influencers.update', ['id' => $influencer->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="page-content">
                <div class="form-container">
                    <div class="panel">
                        <div class="panel-header">
                            <button type="submit" class="btn btn-md btn-primary">
                                Aggiorna
                            </button>
                            <a href="{{route('admin.influencers.search',
                            ['nome' => Session::get('nome_inf'),
                            'cognome' => Session::get('cognome_inf'),
                            'email' => Session::get('email_inf'),
                            'pages' => Session::get('pages_inf'),
                            'page' => Session::get('page_inf')])}}">Indietro</a>
                        </div>
                        <div class="panel-body">
                            <div class="form-group text">
                                <label for="name" class="required">Nome</label>
                                <input type="text" name="nome" id="name" value="{{ $influencer->nome }}" data-vv-as="Nome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Cognome</label>
                                <input type="text" name="cognome" id="name" value="{{ $influencer->cognome }}" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Email</label>
                                <input type="email" name="email" id="name" value="{{ $influencer->email }}" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Telefono</label>
                                <input type="tel" name="telefono" id="name" value="{{ $influencer->telefono }}" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Materiale</label>
                                <input type="text" name="materiale" id="name" value="{{ $influencer->materiale }}" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>

                            <div class="form-group textarea">
                                <label for="cognome">Descrizione</label>
                                <textarea name="descrizione" class="control" id="description" v-validate="''" data-vv-as="Description">{{ $influencer->descrizione }}</textarea>
                            </div>
                            
                            <div class="form-group text">
                                <label for="cognome" class="required">Indirizzo</label>
                                <input type="text" name="indirizzo" id="name" value="{{ $influencer->indirizzo }}" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </form>
        
    </div>
@stop