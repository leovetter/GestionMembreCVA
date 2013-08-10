function chercheAjax(input, e) {
console.log("chercheAjax");
	//Si on a des infos différentes où on a appuyé sur entrée
	if ( (searchVals[$(input).attr("name")] != input.value) || (e.keyCode == 13)) 
	{
		searchVals[$(input).attr("name")] = input.value;
		$("#etudiants").html("<tr><td colspan='4' class='loading'></td></tr>");

		if (typeof xhr !== 'undefined') 
		{
			console.log("xhr.abord();");
			xhr.abort();
		}

		xhr = $.ajax({
			url: "rechercheetudiant",
			data: searchVals
		}).done(function( msg ) {
		console.log("msg = " + msg + "\nsearchVals = " + input.value);
			$("#etudiants").html(msg);
			detailsOnClick();
			gestionScroll();
		});
	}
}

function ajouteAjax() {
console.log("ajouteAjax");
	//Si on est pas déjà en train de charger
	if (changement == true) {
		htmlEtudiant = $("#etudiants").html();

		$("#etudiants").html(htmlEtudiant+"<tr><td colspan='4' class='loading'></td></tr>");

		if (typeof xhr !== 'undefined')
            xhr.abort();

		changement = false;
		xhr = $.ajax({
		  url: "rechercheetudiant",
		  data: searchVals
		}).done(function( msg ) {
			if (msg != "") {
			console.log("msg = " + msg);
				changement = true;
				$("#etudiants").html(htmlEtudiant+msg);
			} else {
			console.log("fail :/");
				toutCharge = true;
				$("#etudiants").html(htmlEtudiant);
			}
			//On relance les script sur le html modifié
			detailsOnClick();
			gestionScroll();
			gestionSearch();
		});
	}
}

function resetKeyup() {
console.log("resetKeyup");
	$("#search input").each(function(i) {
		inputAct = $("#search input")[i];
		$(inputAct).unbind('keyup');
	});
}

function gestionScroll() {
console.log("gestionScroll");
	$(document).scroll(function(e) {
		if (( $(document).scrollTop() >= ($(document).height() - $(window).height() - limitScroll)) && ( toutCharge != true )) 
		{
			searchVals["debut"] = $("#etudiants tr:last-child").attr('id');
			ajouteAjax();
		};
		
		//40 le padding top du body
		if ( $(document).scrollTop() > (2*30+$("#menu").height()) ) {
			scrollSidebar = $(document).scrollTop()-30-$("#menu").height();
		} else {
			scrollSidebar = 0;
		}

		$("#sidebarRight").stop().animate({"top": scrollSidebar}, 300);
	});
}

function gestionSearch() {
	$("#search input")
		.focus(function(e) {
			inputAct = e.currentTarget;

			$(inputAct).bind('keyup', function(e) {
				inputAct = e.currentTarget;

				chercheAjax(inputAct, e);
			});
		})
		.focusout(function(e) {
			resetKeyup();
		});
}

//Tableau de recherche
var searchVals = {}; 
//Requète AJAX
var xhr;
//Limite par rapport au bas de la page à partir de laquelle des nouveau éléments se chargent
const limitScroll = 20;
//Sauvegarde du contenu sdu tableau en cas de chargement suplémentaire
htmlEtudiant = "";
//Gestion du scroll infinite
var changement = true;
var toutCharge = false;

$(function() {
	gestionSearch();

	//INITIALISATION : doit réinitialisé les key up pour tous ;)
	resetKeyup();
	searchVals["debut"] = 0;
	$("#search input").each(function(i) {
		inputAct = $("#search input")[i];
		searchVals[$(inputAct).attr("name")] = inputAct.value;
	});


	

	gestionScroll();
});
