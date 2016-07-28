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

Vous pouvez jouer des actions spécifiques par environnement avant et après le build. Voir les fonctions postdeploy_actions() et predeploy_actions().
@TODO:

    Ajouter une commande --help pour voir comment s'utilisent les paramètres.
    Voir pour utiliser getopt pour récupérer les paramètres du script (http://www.bahmanm.com/blogs/command-line-options-how-to-parse-in-bash-using-getopt)
