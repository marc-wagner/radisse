@extends('layouts.public')

@push('body-scripts')
    <script src="{{ mix('js/home.js') }}"></script>
@endpush


@section('content')

    <div class="homepage-intro">
        <div class="citizen-notes-hand">
            <div class="citizen-notes-hand-container">
                <div class="citizen-note citizen-note-05"></div>
                <div class="citizen-note citizen-note-1"></div>
                <div class="citizen-note citizen-note-5"></div>
                <div class="citizen-note citizen-note-10"></div>
                <div class="citizen-note citizen-note-20"></div>
            </div>
        </div>
        <div class="homepage-intro__intro-text">
            <h2>Le Val’heureux est une monnaie citoyenne</h2>
            <div class="homepage-intro__intro-paragraphs">
                <p>Il circule dans le bassin économique de la région liégeoise : entre Huy et Verviers, en Hesbaye, Condroz, Ourthe-Amblève et dans le pays de Herve.</p>
                <p>Le Val’heureux soutient les entrepreneurs locaux, renforce les circuits courts, et retient et fait circuler la richesse créée dans la région.</p>
            </div>
        </div>
    </div>

    <div class="text text--2columns">
        <div class="text__section">
            <h2>Comment en obtenir ?</h2>
            <p>Le moyen le plus simple pour échanger vos euros contre des Val’heureux est de vous rendre dans un <a href="/comptoirs">comptoir de change</a>.</p>
            <p>Vous pouvez également demander que l’on vous rende la monnaie en Val’heureux quand vous faites vos achats chez un commerçant qui les accepte.</p>
            <p>Enfin, vous pouvez également échanger vos euros lors de nos « <a href="/aperos-du-valheureux">apéros du Val’heureux</a> », tout en en profitant pour rencontrer et boire un verre avec les gens qui font vivre ce projet !</p>
        </div>
        <div class="text__section">
            <h2>Où les utiliser ?</h2>
            <p>Les commerces et prestataires acceptant la monnaie ont généralement un autocollant « Ici circule le Val’heureux » devant chez eux.</p>
            <p>Vous pouvez aussi jeter un œil à la vaste <a href="/partenaires">liste des membres du réseau</a> et ainsi, qui sait, découvrir de nouveaux lieux sympathiques !</p>

            <h2>Être tenu au courant</h2>
            <p>Envie de recevoir, une fois par trimestre, les dernières nouvelles du Val’heureux dans votre boîte mail ? Il vous suffit de vous inscrire ici !</p>

            <form action="{{ route('newsletter-subscription') }}" method="POST"
            class="newsletter-subscription-form">
                <input type="email" name="email" required
                class="newsletter-subscription-form__email-input"
                placeholder="email@exemple.com">
                <button type="submit" class="button">S’inscrire</button>
            </form>

            <p>Vous pourrez vous désinscrire à n’importe quel moment.</p>
        </div>
    </div>

@push('body-styles')
<style>
    .old-bills-announcement {
        display: flex;
        justify-content: center;
        align-items: center;
        max-width: 42rem;
        margin-left: auto;
        margin-right: auto;
        padding-left: 1.6rem;
        padding-right: 1.6rem;
        margin-bottom: 2em;
        font-size: 1.4rem;
        line-height: 1.8rem;
    }
    .icon--information {
        width: 2.5rem;
        margin-right: 1.5rem;
        fill: rgb(0, 97, 116);
    }
    @media (min-width: 750px) {
        .old-bills-announcement {
            max-width: 62rem;
            font-size: 2rem;
            line-height: 2.5rem;
        }
    }
    @media (min-width: 1000px) {
        .old-bills-announcement {
            max-width: 80rem;
        }
    }
</style>
@endpush

    <div class="old-bills-announcement">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon--information"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg>
        </div>
        <div>
            <p>
                Vous possédez encore des anciens billets de valeureux ? Vous pouvez les échanger contre de nouveaux billets lors de nos <a href="/aperos-du-valheureux">apéros</a>, ou bien en contactant l’ASBL le Val’heureux.
                <a href="/remplacement-anciens-billets">Cliquez ici pour plus d’infos</a>.
            </p>
        </div>
    </div>

@endsection
