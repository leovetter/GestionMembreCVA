stopLoad();	
fillDataTable();
function fermerLigne ( i ) {
	ligneAct = $("#etudiants tr")[i];
	//Ligne du dessous qui contient les details
	ligneDetail = $("#detailed-body tr")[i];

	//On la cache
	//$(ligneDetail).fadeOut(0);
	$(ligneAct).toggleClass("hover");
}

function startLoad() {
	Load = $(".loading");	
	Load.fadeIn(0);	
	$('.infos').fadeOut(0);
}
function stopLoad() {
	Load = $(".loading");	
	$('.infos').fadeIn(0);
	Load.fadeOut(0);	
}

function ouvrirLigne ( i ) {
	startLoad();
	ligneAct = $("#etudiants tr")[i];
	//Ligne du dessous qui contient les details
	ligneDetail = $("#detailed-body tr")[i];
	
	numEtu = $($("#etudiants tr")[i]).attr('numEtu');
	$('.details').load('detailetudiant?etudiant=' + numEtu, function() {
        stopLoad();
		//$('.details').text() = '<tr class="loading"><td><img src="http://authenticate.hublot.com/interface/img/icons/loading.gif" /></td></tr>';

      });
	  
	//$('.details').text() = '<tr class="loading"><td><img src="http://authenticate.hublot.com/interface/img/icons/loading.gif" /></td></tr>';

	//On l'affiche
	//$(ligneDetail).fadeIn(0);
	$(ligneAct).toggleClass("hover");
}

function voir(idEtu) {

	$.get("voirDetails?idEtu=" + idEtu,
	function(msg){
		$("#voirEtudiant").html(msg);
	});
}

/*function detailsOnClick() {
	
	$("#etudiants tr").each(function(i) {
		ligneAct = $("#etudiants tr")[i];
		
		$(ligneAct).click(function() {
		
			//Si une ligne deja selectionné
			if ( idSelected != -1 ) {
				//On ferme celle actuellement ouverte
				
				//Si on clique sur la ligne actuellement ouverte
				if ( i == idSelected ) {
					//Plus aucune n'est alors sélectionné
					//idSelected = -1;
				}
				//Si on clique en dehors de la ligne selectionné
				else {
					//On ouvre la nouvelle
					fermerLigne(idSelected);
					ouvrirLigne(i);
					idSelected = i;
				}
			}
			//Si aucune ligne selectionné
			else {
				//On ouvre l'actuelle
				ouvrirLigne(i);
				idSelected = i;
			}
		});
		
		/*$(ligneAct).dblclick(function() {
			//Id de l'étudiant cliqué
			idAct = $($("#etudiants tr")[i]).attr('id');
			
			var newUrl = "test.html?idEtudiant="+idAct;
			window.location.replace(newUrl);
		});/
	});
	
}*/

//Permet de savoir quelle ligne est selectionné. -1 si aucune
var idSelected = -1;

/*$(function() {
	detailsOnClick() ;
});*/

function fillDataTable() {	
				var TableAssemblees = $('#table_adherent').dataTable({
					"oLanguage": {
						"sProcessing":     "Traitement en cours...",
						"sSearch":         "Rechercher&nbsp;:",
						"sInfo":           "Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
						"sInfoEmpty":      "Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
						"sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
						"sInfoPostFix":    "",
						"sLoadingRecords": "Chargement en cours...",
						"sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
						"sZeroRecords":    "Aucun r&eacute;sultat ne correspond à votre recherche.",
						"sEmptyTable":     "Aucune donnée disponible dans le tableau",
						"oPaginate": {
							"sFirst":      "Premier",
							"sPrevious":   "Pr&eacute;c&eacute;dent",
							"sNext":       "&nbsp;Suivant",
							"sLast":       "Dernier"
						},
						"oAria": {
							"sSortAscending":  ": activer pour trier la colonne par ordre croissant",
							"sSortDescending": ": activer pour trier la colonne par ordre décroissant"
						}
					}
				});
}
