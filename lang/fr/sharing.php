<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Sharing page
	|--------------------------------------------------------------------------
	*/
    'title' => 'Partage',
    'info' => 'Cette page offre un aperçu des droits de partage associés aux albums, avec la possibilité de les modifier.',
    'album_title' => 'Titre de l’album',
    'username' => 'Nom d’utilisateur',
    'no_data' => 'La liste de partage est vide.',
    'share' => 'Partager',
    'add_new_access_permission' => 'Ajouter une nouvelle autorisation d’accès',
    'permission_deleted' => 'Autorisation supprimée !',
    'permission_created' => 'Autorisation créée !',
    'propagate' => 'Propager',
    'propagate_help' => 'Propager les autorisations d’accès actuelles à tous les descendants<br>(sous-albums et leurs sous-albums, etc.)',
    'propagate_default' => 'Par défaut, les autorisations existantes (album-utilisateur)<br>sont mises à jour et les absentes ajoutées.<br>Les autorisations supplémentaires non présentes dans cette liste sont laissées telles quelles.',
    'propagate_overwrite' => 'Écraser les autorisations existantes au lieu de les mettre à jour.<br>Cela supprimera aussi toutes les autorisations absentes de cette liste.',
    'propagate_warning' => 'Cette action est irréversible.',
    'permission_overwritten' => 'Propagation réussie ! Autorisations écrasées !',
    'permission_updated' => 'Propagation réussie ! Autorisations mises à jour !',
    'bluk_share' => 'Partage en groupe',
    'bulk_share_instr' => 'Sélectionnez plusieurs albums et utilisateurs pour partager.',
    'albums' => 'Albums',
    'users' => 'Utilisateurs',
    'no_users' => 'Pas d\'utilisateurs disponibles.',
    'no_albums' => 'Pas d\'album disponible.',
    'grants' => [
        'read' => 'Autorise la lecture',
        'original' => 'Autorise l’accès aux photos originales',
        'download' => 'Autorise le téléchargement',
        'upload' => 'Autorise l’envoi de fichiers',
        'edit' => 'Autorise la modification',
        'delete' => 'Autorise la suppression',
    ],
];
