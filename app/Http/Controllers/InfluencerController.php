<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Influencer;
use App\Models\InfluencerOrder;
use Illuminate\Support\Facades\Session;

class InfluencerController extends Controller{

    public function index(){
        $influencers = Influencer::paginate(10);

        Session::pull('nome_inf');
        Session::pull('cognome_inf');
        Session::pull('email_inf');
        Session::pull('pages_inf');

        if(isset(request()->query()['page']) && request()->query()['page'] !== null){
            Session::put('page_inf', request()->query()['page']);
        }
        return view('influencers.index', ['influencers' => $influencers]);
    }

    public function create(){
        return view('influencers.create');
    }

    public function store(Request $request){
        Influencer::create([
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'materiale' => $request->materiale,
            'descrizione' => $request->descrizione,
            'indirizzo' => $request->indirizzo
        ]);

        return redirect()->route('admin.influencers');
    }

    public function edit($id){
        $influencer = Influencer::findOrFail($id);
        return view('influencers.edit', ['influencer' => $influencer]);
    }

    public function update(Request $request, $id){
        $influencer = Influencer::findOrFail($id);

        $influencer->update([
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'materiale' => $request->materiale,
            'descrizione' => $request->descrizione,
            'indirizzo' => $request->indirizzo
        ]);

        //return redirect()->route('admin.influencers');
        return redirect()->route('admin.influencers.search',
                    ['nome' => Session::get('nome_inf'),
                    'cognome' => Session::get('cognome_inf'),
                    'email' => Session::get('email_inf'),
                    'pages' => Session::get('pages_inf'),
                    'page' => Session::get('page_inf')]);
    }

    public function destroy($id){
        $influencer = Influencer::where('id', $id)->first();
        //return redirect()->route('admin.influencers');
        InfluencerOrder::where('email', $influencer->email)->delete();
        $influencer->delete();
        return redirect()->route('admin.influencers.search',
                    ['nome' => Session::get('nome_inf'),
                    'cognome' => Session::get('cognome_inf'),
                    'email' => Session::get('email_inf'),
                    'pages' => Session::get('pages_inf'),
                    'page' => Session::get('page_inf')]);
    }

    public function listOrders(){
        return redirect()->route('admin.influencers');
    }

    public function search(){
        $pages = 10;

        if (request()->pages != null && request()->pages > 0){
            $pages = request()->pages;
            Session::put('pages_inf', $pages);
        }else{
            Session::pull('pages_inf');
        }

        if (request()->nome != null){
            Session::put('nome_inf', request()->nome);
        }else{
            Session::pull('nome_inf');
        }

        if (request()->cognome != null){
            Session::put('cognome_inf', request()->cognome);
        }else{
            Session::pull('cognome_inf');
        }

        if (request()->email != null){
            Session::put('email_inf', request()->email);
        }else{
            Session::pull('email_inf');
        }

        if (request()->page != null){
            Session::put('page_inf', request()->page);
        }else{
            Session::put('page_inf', 1);
        }
        
        $influencers = Influencer::where('nome', 'like', '%'.request()->nome.'%')
                ->where('cognome', 'like', '%'.request()->cognome.'%')
                ->where('email', 'like', '%'.request()->email.'%')
                ->paginate($pages);

        $search = [
            'nome' => request()->nome,
            'cognome' => request()->cognome,
            'email' => request()->email,
            'pages' => $pages
        ];

        return view('influencers.index', ['influencers' => $influencers, 'search' => $search]);
    }
}
