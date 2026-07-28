import { Link } from 'react-router';

export const RegisterIntro = () => (
  <section className="register-intro">
    <p className="register-intro__eyebrow">Rejoindre Hociatec</p>
    <h1 className="register-intro__title">Créez votre espace client et accédez à nos services numériques</h1>
    <p className="register-intro__subtitle">En quelques minutes, activez un compte sécurisé pour piloter vos projets, suivre vos demandes de support et collaborer avec notre équipe d&apos;experts.</p>
    <ul className="register-highlights"><li>Suivi de vos projets en temps réel</li><li>Support prioritaire et notifications personnalisées</li><li>Tableaux de bord et documentations centralisés</li></ul>
    <p className="register-intro__switch">Déjà client ? <Link to="/login" className="register-intro__switch-link">Se connecter</Link></p>
  </section>
);
