# lib-bundle

Ce package contient les fonctionnalités et helpers de base utilisables dans tous les projets Khalil1608.

## Comment publier une nouvelle version du package

** Etape 1 : tagger la nouvelle version**

Editer le composer.json et mettre à jour la version.
- si c'est un changement cassant, mettre à jour la version mineure : "version": "4.**1**.58"
- sinon, mettre à jour la version de patch : "version": "4.1.**58**"

```
git commit -am "bump to version 4.1.58"
git push origin master
git tag -a v4.1.58
git push origin v4.1.58
```
Voila, la nouvelle version est pushée.

** Etape 2 : mise à jour des projets concernés

```
cd www-platform
updateBundles
git commit -am "update lib-bundle to 4.1.58"
git push origin mabranche
```
