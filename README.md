Script de build pour les projets.

Les options par défaut considèrent que la production est mise à jour et génère un backup.

Usage : ./build.sh --env dev --mode install --backup 1 --uri http://hc.fun

Usage court : ./build.sh --e dev --m install --b 1 --u http://hc.fun

Environnements connus :

    dev
    recette
    preprod
    prod

Modes connus :

    install
    update

Installation

Pour pouvoir jouer le script de build, créer :
* Un fichier install.sh qui liste les actions à jouer de l'installation du site
* Un fichier update.sh qui liste les actions à jouer de la mise à jour du site
* Un fichier predeploy_actions.sh qui joue des actions particulières avant de construire le site
* Un fichier postdeploy_actions.sh qui joue des actions particulières après avoir construit le site

Des fichiers d'exemple sont disponibles dans le dossier scripts.

@TODO:
* Voir pour utiliser getopt pour récupérer les paramètres du script (http://www.bahmanm.com/blogs/command-line-options-how-to-parse-in-bash-using-getopt)
