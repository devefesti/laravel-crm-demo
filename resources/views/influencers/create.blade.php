@extends('admin::layouts.master')

@section('page_title')
    {{ __('Create influencers') }}
@stop

@section('content-wrapper')
    <div class="content full-page dashboard">
        <h1>
            {{ Breadcrumbs::render('influencers') }}
        
            {{ __('Influencers') }}
        </h1>

        <form action="{{ route('admin.influencers.store') }}" method="POST">
            @csrf
            <div class="page-content">
                <div class="form-container">
                    <div class="panel">
                        <div class="panel-header">
                            <button type="submit" class="btn btn-md btn-primary">
                                Crea
                            </button>
                            <a href="http://127.0.0.1:8000/admin/influencers">Indietro</a>
                        </div>
                        <div class="panel-body">
                            <div class="form-group text">
                                <label for="name" class="required">Nome</label>
                                <input type="text" name="nome" id="name" value="" data-vv-as="Nome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Cognome</label>
                                <input type="text" name="cognome" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Email</label>
                                <input type="email" name="email" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Telefono</label>
                                <input type="tel" name="telefono" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                            <div class="form-group text">
                                <label for="cognome" class="required">Materiale</label>
                                <input type="text" name="materiale" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>

                            <div class="form-group textarea">
                                <label for="cognome">Descrizione</label>
                                <textarea name="descrizione" class="control" id="description" v-validate="''" data-vv-as="Description"></textarea>
                            </div>
                            
                            <div class="form-group text">
                                <label for="cognome" class="required">Indirizzo</label>
                                <input type="text" name="indirizzo" id="name" value="" data-vv-as="Cognome" class="control" aria-required="true" aria-invalid="false" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </form>
        
    </div>
@stop