fillDataTable();

function voir(idEtu) {

	$.get("voirDetails?idEtu=" + idEtu,
	function(msg){
		$("#voirEtudiant").html(msg);
	});
}

function createCSV(obj){
	var tab=document.getElementById(obj);
	var TabLignes=tab.getElementsByTagName('tr');
	var csvText="";
	var ArrLine=new Array();

	//Les en-têtes
	TabHead=TabLignes[0].getElementsByTagName('th');
	for(var z=0; z<TabHead.length-1;z++){
		ArrLine.push(TabHead[z].innerHTML);
	}
	csvText+=ArrLine.join(';')+'\n';

	//Les lignes avec le contenu
	var x=1;
	while(TabLignes[x]){
		TabCol=TabLignes[x].getElementsByTagName('td');
		ArrLine = new Array();		
		for(var y=0;y<(TabCol.length-1);y++){
			
			ArrLine.push(TabCol[y].innerHTML);
		}
		csvText+=ArrLine.join(';')+'\n';
		x++;
	}
	document.getElementById("csvText").value=csvText;
	document.forms[0].submit();
}


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
