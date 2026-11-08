/**
 * Philippine Hierarchical Address Data (Province -> Municipality/City -> Barangay)
 * Tailored for BloodLink with comprehensive Region II (Isabela, Cagayan, Nueva Vizcaya, Quirino),
 * NCR, Central Luzon, and nationwide coverage.
 */
const PH_ADDRESS_DATA = {
  "Isabela": {
    "Santiago City": [
      "Abra", "Ambalatungan", "Balintocatoc", "Baluarte", "Bannawag Norte", "Batal", "Buenavista",
      "Cabulay", "Calao East (Poblacion)", "Calao West (Poblacion)", "Caliat", "Centro East (Poblacion)",
      "Centro West (Poblacion)", "Divisoria", "Dubinan East", "Dubinan West", "Luna", "Mabini",
      "Malvar", "Nabbuan", "Patul", "Plaridel", "Rizal", "Rosario", "Sagana", "Salvacion",
      "San Andres", "San Isidro", "San Jose", "Santa Rosa", "Sinsayon", "Victory Norte", "Victory Sur",
      "Villasis"
    ],
    "City of Ilagan": [
      "Alibagu", "Allaguia", "Bagong Silang", "Baligatan", "Bliss Village", "Calamagui 1st", "Calamagui 2nd",
      "Camunatan", "Centro Poblacion", "Centro San Antonio", "Fugu", "Guinatan", "Malalam", "Marana 1st",
      "Marana 2nd", "Marana 3rd", "Nag-o-nay", "Osmeña", "Paliueg", "San Felipe", "San Ignacio",
      "San Juan", "San Lorenzo", "San Mateo", "San Rodrigo", "San Vicente", "Santa Barbara", "Santa Catalina",
      "Santa Isabel", "Santa Maria", "Santo Tomas", "Siffu", "Sindun Bayabo", "Sindun Maride", "Sipay"
    ],
    "City of Cauayan": [
      "Alicaocao", "Amobocan", "Andarayan", "Bantug", "Buena Suerte", "Bugallon", "Cabaruan",
      "Cabugao", "Carabatan Chica", "Carabatan Grande", "Cassap Fuera", "Catalina", "Culalabat",
      "Dabburab", "De Sisto", "District 1 (Poblacion)", "District 2 (Poblacion)", "District 3 (Poblacion)",
      "Forest Region", "Gagabutan", "Gappal", "Labinab", "Linglingay", "Mabantad", "Maligaya",
      "Manaoag", "Marabulig 1", "Marabulig 2", "Minante 1", "Minante 2", "Naganacan", "Nagrumbuan",
      "Nungnungan 1", "Nungnungan 2", "Pinoma", "Rizal", "San Fermin", "San Isidro", "San Luis",
      "San Pablo", "Sillawit", "Sinippil", "Tagaran", "Turayong", "Union", "Villa Concepcion", "Villa Luna"
    ],
    "San Mariano": [
      "Alibadbad", "Balagan", "Binatug", "Bitabian", "Buyasan", "Cadsalan", "Casala", "Cataguing",
      "Daragutan East", "Daragutan West", "Del Pilar", "Dibuluan", "Dicamay", "Dipusu", "Disulap",
      " Libertad", "Macayucayu", "Mallabo", "Marannao", "Minanga", "Old San Mariano", "Palawan",
      "Poblacion", "San Jose", "San Pablo", "San Pedro", "Santa Cruz", "Tappa", "Ueg", "Zamora"
    ],
    "Echague": [
      "Annafunan", "Arabiat", "Aroma", "Babaran", "Bacaca", "Benguet", "Buneg", "Cabugao",
      "Canangaan", "Castillo", "Dicamay", "Gumbauan", "Ipil", "Maligaya", "Munggayao", "Pangal Sur",
      "Poblacion 1", "Poblacion 2", "Poblacion 3", "San Antonio", "San Fabian", "San Felipe",
      "San Juan", "Silauan Norte", "Silauan Sur", "Villa Remedios", "Villa Victoria"
    ],
    "Alicia": [
      "Amparo", "Apanay", "Aurora", "Bagnos", "Burgos", "Calaocan", "Callao", "Centro (Poblacion)",
      "Linglingay", "Mabini", "Magsaysay", "Mataas na Kahoy", "Padre Faura", "Rizaluna", "Salvacion",
      "San Antonio", "San Fernando", "San Francisco", "San Juan", "San Pedro", "Santa Cruz", "Santa Maria"
    ],
    "Roxas": [
      "Bantug (Poblacion)", "Doña Concepcion", "Imbiao", "Lanting", "Lucban", "Luna", "Marcos",
      "Masigun", "Matusalem", "Muñoz", "Quiling", "Rang-ayan", "Rizal (Poblacion)", "San Antonio",
      "San Jose", "San Luis", "San Pedro", "San Placido", "San Rafael", "Simimbaan", "Sotero Nacion",
      "Villa Concepcion", "Vira (Poblacion)"
    ],
    "Cabagan": [
      "Anag", "Angancasilian", "Balasig", "Cansan", "Casibarag Norte", "Casibarag Sur", "Catabayungan",
      "Centro (Poblacion)", "Cubag", "Garita", "Luquilu", "Magassi", "Ngarag", "Saui", "Tallag"
    ],
    "Tumauini": [
      "Annafunan", "Antagan 1st", "Antagan 2nd", "Arcon", "Balug", "Bantug", "Bayabo East",
      "Camp Samal", "Compania", "Fermeldy", "Lanna", "Lapogan", "Malamag East", "Malamag West",
      "Maligaya", "Minanga", "Poblacion 1", "Poblacion 2", "Poblacion 3", "Poblacion 4", "San Mateo",
      "San Pedro", "Santa", "Santa Catalina", "Santa Visitacion", "Santo Niño", "Sinippil"
    ],
    "San Mateo": [
      "Baclaran", "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)",
      "Barangay 4 (Poblacion)", "Dagupan", "Darapan", "Estrella", "Gaddanan", "Malasin", "Mararigue",
      "Old Centro 1", "Old Centro 2", "Salapan", "San Andres", "San Antonio", "San Manuel", "Sinamar"
    ],
    "Angadanan": ["Centro 1 (Poblacion)", "Centro 2 (Poblacion)", "Campamento", "Calaocan", "Bunnay", "Dalig", "Loria", "Minanga", "Rancho", "San Isidro", "Viga"],
    "Aurora": ["Ballesteros", "Bannawag", "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", "Barangay 4 (Poblacion)", "Kalingami", "Macatal", "San Andres", "Santa Rita", "Victoria"],
    "Benito Soliven": ["Andabuen", "Ara", "Capuseran", "Dagupan", "District 1 (Poblacion)", "District 2 (Poblacion)", "Guilingan", "Maluno Norte", "Maluno Sur", "San Carlos", "Santa Cruz", "Yebaw"],
    "Burgos": ["Bacnor East", "Bacnor West", "Calabayan", "Catabban", "Cullalabo East", "Cullalabo West", "Dalig", "Malasin", "Poblacion", "San Antonino", "San Bonifacio"],
    "Cabatuan": ["Calaocan", "Centro (Poblacion)", "Cullalabo", "Del Pilar", "Diamantina", "La Paz", "Lucrecia", "Macaniao", "Madanggay", "Magnwang", "Namnama", "Nueva Era", "Sampaloc", "San Andres"],
    "Cordon": ["Aguinaldo", "Caquilingan", "Capirpirwan", "Dallao", "Gayong", "Malabing", "Magsaysay", "Poblacion", "Quezon", "Rizal", "San Antonino", "Talictic", "Tanggal", "Villabuena", "Wigan"],
    "Dinapigue": ["Ayod", "Bucal Sur", "Dibulo", "Digumased (Poblacion)", "Dimaluade", "Waldorf"],
    "Divilacan": ["Bicobian", "Dilakit", "Dimapnat", "Dimasalansan", "Dimapula (Poblacion)", "Ditarum"],
    "Gamu": ["Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Barangay 3 (Poblacion)", "Camp Melchor F. Dela Cruz", "Furao", "Guibang", "Linglingay", "Mabini", "Songsong", "Union"],
    "Jones": ["Abulan", "Addalam", "Arunganan", "Bantay", "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Dicamay 1", "Dicamay 2", "Dipungo", "Linamanan", "Malannao", "San Antonio", "Tupax"],
    "Luna": ["Bantug", "Centro 1 (Poblacion)", "Centro 2 (Poblacion)", "Concepcion", "Harana", "Lalog", "Luyao", "Macañao", "Puroc", "San Isidro", "San Miguel"],
    "Maconacon": ["Aplaya (Poblacion)", "Canadam", "Eleonor", "Fely", "Malasin", "Minanga", "Santa Marina"],
    "Mallig": ["Bannawag", "Bimmonton", "Casili", "Centro 1 (Poblacion)", "Centro 2 (Poblacion)", "Manano", "Olango", "San Jose Norte", "San Jose Sur", "San Pedro", "Siempre Viva Norte", "Trinidad"],
    "Naguilian": ["Aguinaldo", "Bagong Sikat", "Burgos", "Flores", "La Union", "Magsaysay", "Minallo", "Poblacion", "Quezon", "San Manuel", "Santa Victoria", "Santo Tomas"],
    "Palanan": ["Alsam", "Bissag", "Culasi", "Dialao", "Dicabisagan East (Poblacion)", "Dicabisagan West", "Didaddungan", "Dimalicu-lic", "Dimatican", "Maligaya", "San Isidro"],
    "Quezon": ["Abut", "Alunan", "Arellano", "Aurora", "Barangay 1 (Poblacion)", "Barangay 2 (Poblacion)", "Minagbag", "San Alejandro", "San Juan", "Santos", "Turod"],
    "Quirino": ["Bannawag", "Cabaruan", "Dolores", "Luna", "Manaois", "Poblacion", "San Isidro", "San Jose", "San Mateo", "Santa Catalina", "Santa Lucia", "Santo Domingo", "Sinait", "Suerte"],
    "Ramon": ["Ambatali", "Bugallon Proper", "Burgos", "General Aguinaldo", "Nagbacalan", "Oscariz", "Poblacion 1", "Poblacion 2", "Poblacion 3", "Planag", "Rancho", "San Antonio", "San Sebastiano"],
    "Reina Mercedes": ["Banuag", "Cutog Grande", "Cutog Pequeño", "District 1 (Poblacion)", "District 2 (Poblacion)", "Labinab Pequeño", "Mallalatang Grande", "Napaccu Grande", "Salucong", "Santiago", "Villador"],
    "San Agustin": ["Bautista", "Calaocan", "Dabubu Grande", "Dabubu Pequeño", "Masaya Centro (Poblacion)", "Masaya Norte", "Masaya Sur", "Nemmatan", "Palacian", "San Antonio", "Santo Niño"],
    "San Guillermo": ["Burgos", "Calanigan Norte", "Calanigan Sur", "Centro 1 (Poblacion)", "Centro 2 (Poblacion)", "Colorado", "Dietban", "Dingading", "Dipuso", "Estrella", "Guinatan", "Palawan"],
    "San Isidro": ["Campos", "Cebu", "Gomez", "Gud", "La Torre", "Patul", "Poblacion", "Quezon", "Ramos", "San Marcial", "Victoria"],
    "San Manuel": ["Agliam", "Babbayan", "Cabacabaan", "District 1 (Poblacion)", "District 2 (Poblacion)", "District 3 (Poblacion)", "District 4 (Poblacion)", "Eden", "Malabbac", "San Francisco", "Villador"],
    "San Pablo": ["Auitan", "Bungad", "Caddayan", "Calamagui", "Caralucud", "Centro (Poblacion)", "Dalena", "Guminga", "Minanga Norte", "Minanga Sur", "San Jose", "Simanu Norte", "Simanu Sur"],
    "Santa Maria": ["Bangad", "Buenavista", "Calamagui North", "Calamagui South", "Mozozoit", "Naganacan", "Poblacion 1", "Poblacion 2", "Poblacion 3", "Quinagabian", "San Antonio", "San Isidro"],
    "Santo Tomas": ["Amugugan", "Antagan", "Bagabag", "Balagan", "Bolinao", "Cañogan Abajo", "Cañogan Alto", "Centro", "Colunguan", "Malalam", "Poblacion", "San Bernardo", "San Rafael Abajo", "San Roque"]
  },
  "Cagayan": {
    "Tuguegarao City": [
      "Annafunan East", "Annafunan West", "Atulayan Norte", "Atulayan Sur", "Bagay", "Buntun",
      "Caggay", "Capatan", "Carig Norte", "Carig Sur", "Caritan Centro", "Caritan Norte", "Caritan Sur",
      "Cataggaman Nuevo", "Cataggaman Pardo", "Cataggaman Viejo", "Centro 01 (Poblacion)", "Centro 02 (Poblacion)",
      "Centro 03 (Poblacion)", "Centro 04 (Poblacion)", "Centro 05 (Poblacion)", "Centro 06 (Poblacion)",
      "Centro 07 (Poblacion)", "Centro 08 (Poblacion)", "Centro 09 (Poblacion)", "Centro 10 (Poblacion)",
      "Centro 11 (Poblacion)", "Centro 12 (Poblacion)", "Dadda", "Gosi Norte", "Gosi Sur", "Larion Alto",
      "Larion Bajo", "Leonarda", "Libag Norte", "Libag Sur", "Linao East", "Linao Norte", "Linao West",
      "Nambalan Norte", "Nambalan Sur", "Pallua Norte", "Pallua Sur", "Pengue-Ruyu", "San Gabriel", "Tagga", "Ugac Norte", "Ugac Sur"
    ],
    "Aparri": ["Aparri East", "Aparri West", "Backiling", "Bangag", "Binalan", "Bisagu", "Bukig", "Bulala Norte", "Bulala Sur", "Centro 1 (Poblacion)", "Centro 2", "Centro 3", "Centro 4", "Centro 5", "Centro 6", "Centro 7", "Centro 8", "Centro 9", "Centro 10", "Centro 11", "Centro 12", "Centro 13", "Centro 14", "Centro 15", "Maura", "Minanga", "Punta", "San Antonio", "Toran"],
    "Solana": ["Andarayan North", "Andarayan South", "Banuang", "Basi East", "Basi West", "Bauange", "Calaoagan", "Centro East (Poblacion)", "Centro Northeast", "Centro Northwest", "Centro Southeast", "Centro Southwest", "Iraga", "Lanna", "Maddarulug", "Malalam-Malabbac", "Nangalisan", "Padre Burgos", "Sampaguita", "Ubong"],
    "Baggao": ["Alba", "Annayatan", "Asassi", "Awitan", "Bagunot", "Barsat East", "Barsat West", "Bitag Grande", "Canangatan", "Centro (Poblacion)", "Dabbac Grande", "Imurung", "Mabini", "Nangalinan", "Remus", "San Isidro", "San Jose", "Tallang"],
    "Enrile": ["Alibago", "Barangay 1", "Barangay 2", "Barangay 3 (Poblacion)", "Barangay 4", "Divisoria", "Inga", "Lanna", "Maddarulug", "Maracuru", "Roma Norte", "Roma Sur", "San Roque"],
    "Peñablanca": ["Aggugaddan", "Alimanao", "Baliuag", "Bical", "Bugatay", "Camasi", "Centro (Poblacion)", "Dodangan", "Malibabag", "Manga", "Minanga", "Nangalditan", "Patung", "Quibal", "San Roque"]
  },
  "Nueva Vizcaya": {
    "Bayombong": ["Bonfal East", "Bonfal West", "Bonfal Proper", "Cabuaan", "Casat", "District III (Poblacion)", "District IV (Poblacion)", "Don Domingo Maddela", "Don Tomas Maddela", "La Torre North", "La Torre South", "Magat", "Magsaysay", "Paitan", "Salvacion", "San Nicolas", "Santa Rosa", "Vista Alegre"],
    "Solano": ["Aggub", "Bangaan", "Bangar", "Bascaran", "Communal", "Concepcion", "Curifang", "Dadap", "General Luna", "Osmeña", "Poblacion North", "Poblacion South", "Quezon", "Quirino", "Roxas", "San Juan", "San Luis", "Tucal", "Uddiawan"],
    "Bambang": ["Aliaga", "Almaguer North", "Almaguer South", "Banggot (Poblacion)", "Buag (Poblacion)", "Calaocan (Poblacion)", "Homestead", "Ineangan", "Macabenga", "Mabuslo", "Mauan", "Pinasling", "San Antonio", "San Fernando", "Santo Domingo"]
  },
  "Quirino": {
    "Cabarroguis": ["Banuar", "Burgos", "Calaoagan", "Del Pilar", "Dibibi", "Dingasan", "Eden", "Gomez", "Mangandingay", "San Marcos", "Villamor", "Zamora"],
    "Diffun": ["Andres Bonifacio", "Aurora East", "Aurora West", "Baguio Village", "Balagbag", "Campamento", "Caria", "Dumanisi", "Gabriela Silang", "Gulac", "Liwayway", "Magsaysay", "Makate", "Maria Clara", "Poblacion", "Ricarte", "San Antonio", "San Isidro", "San Leonardo", "Villa Pascua"],
    "Maddela": ["Abbag", "Balligui", "Buenavista", "Cabaruan", "Cabua-an", "Cofcaville", "Diduyon", "Dipintin", "Divisoria Norte", "Divisoria Sur", "Dumabato Norte", "Dumabato Sur", "Lusod", "Manglad", "Pedlisan", "Poblacion Norte", "Poblacion Sur", "San Martin", "San Pedro", "San Salvador", "Santo Niño", "Villa Hermosa", "Villa Ylanan"]
  },
  "Metro Manila (NCR)": {
    "Manila": [
      "Binondo", "Ermita", "Intramuros", "Malate", "Paco", "Pandacan", "Port Area",
      "Quiapo", "Sampaloc", "San Andres", "San Miguel", "San Nicolas", "Santa Ana",
      "Santa Cruz", "Santa Mesa", "Tondo 1", "Tondo 2"
    ],
    "Quezon City": [
      "Bagong Silangan", "Batasan Hills", "Commonwealth", "Holy Spirit", "Payatas",
      "Cubao", "Diliman", "Fairview", "Greater Lagro", "Loyola Heights", "New Manila",
      "Novaliches Proper", "Project 4", "Project 6", "San Bartolome", "Tandang Sora"
    ],
    "Caloocan City": ["Grace Park East", "Grace Park West", "Bagong Barrio", "Bagumbong", "Camarin", "Deparo", "Tala", "Maypajo"],
    "Taguig City": ["Bicutan", "Bonifacio Global City (Fort Bonifacio)", "Central Signal Village", "Hagonoy", "Lower Bicutan", "Maharlika Village", "Napindan", "Pinagsama", "Tanyag", "Upper Bicutan", "Ususan", "Western Bicutan"],
    "Pasig City": ["Bagong Ilog", "Kapitolyo", "Manggahan", "Maybunga", "Oranbo", "Pinagbuhatan", "Rosario", "San Antonio", "San Joaquin", "Santolan", "Ugong"],
    "Makati City": ["Bel-Air", "Carmona", "Dasmarinas", "Forbes Park", "Guadalupe Nuevo", "Guadalupe Viejo", "Kasilawan", "Magallanes", "Palanan", "Poblacion", "San Antonio", "San Lorenzo", "Urdaneta"],
    "Mandaluyong City": ["Addition Hills", "Barangka", "Highway Hills", "Malamig", "Plainview", "Pleasant Hills", "Wack-Wack Greenhills"],
    "Marikina City": ["Barangka", "Concepcion Uno", "Concepcion Dos", "Fortune", "Industrial Valley", "Marikina Heights", "Nangka", "Parang", "San Roque", "Santa Elena", "Santo Niño", "Tumana"],
    "Pasay City": ["Baclaran", "Cartimar", "Domestic Airport Area", "Malibay", "Maricaban", "Poblacion", "San Isidro", "San Jose", "San Rafael", "Villamor Airbase"],
    "Parañaque City": ["Baclaran", "BF Homes", "Don Bosco", "Don Galo", "La Huerta", "Moonwalk", "San Antonio", "San Dionisio", "San Martin de Porres", "Santo Niño", "Sun Valley", "Tambi"],
    "Las Piñas City": ["Almanza Uno", "Almanza Dos", "BF International", "Daniel Fajardo", "Pamplona Uno", "Pamplona Dos", "Pilar", "Pulang Lupa Uno", "Pulang Lupa Dos", "Talon Uno", "Talon Dos", "Zapote"],
    "Muntinlupa City": ["Alabang", "Ayala Alabang", "Bayanan", "Buli", "Cupang", "Poblacion", "Putatan", "Sucat", "Tunasan"],
    "Valenzuela City": ["Gen. T. de Leon", "Karuhatan", "Malanday", "Marulas", "Maysan", "Poblacion", "Punturin", "Ugong"],
    "Malabon City": ["Acacia", "Baritan", "Concepcion", "Flores", "Hulong Duhat", "Longos", "Poblacion", "Potrero", "San Agustin", "Tañong", "Tonsuya"],
    "Navotas City": ["Bagumbayan North", "Bagumbayan South", "Bangculasi", "Daanghari", "North Bay Boulevard", "San Jose", "San Roque", "Tangos"],
    "San Juan City": ["Addition Hills", "Greenhills", "Little Baguio", "Pedro Cruz", "Progreso", "Rivera", "San Perfecto", "Tibagan", "West Crame"],
    "Pateros": ["Aguho", "Magtanggol", "Martires del 96", "Poblacion", "San Pedro", "San Roque", "Santa Ana", "Santo Rosario-Kanluran", "Santo Rosario-Silangan", "Tabacalera"]
  },
  "Pangasinan": {
    "Dagupan City": ["Bonuan Boquig", "Bonuan Binloc", "Bonuan Gueset", "Downtown (Poblacion)", "Lucao", "Mayois", "Pantabangan", "Pantal", "Tambac"],
    "Urdaneta City": ["Anonas", "Bactad East", "Bayaoas", "Camantiles", "Catablan", "Nancayasan", "Pinmaludpod", "Poblacion", "San Jose", "Santa Lucia"],
    "San Carlos City": ["Abanon", "Agdao", "Balite", "Bocboc", "Bolingit", "Coliling", "Mabalbalino", "Pagal", "Poblacion", "Talang"],
    "Lingayen": ["Aliwekwek", "Baay", "Balococ", "Bantayan", "Capandanan", "Domalandan Center", "Poblacion", "Quibaol", "Tonton"]
  },
  "Pampanga": {
    "San Fernando City": ["Calulut", "Del Carmen", "Del Pilar", "Dolores", "Magliman", "Maimpis", "Poblacion", "Quebiauan", "San Agustin", "San Jose", "Sindalan", "Telebastagan"],
    "Angeles City": ["Balibago", "Cutcut", "Malabanias", "Pandan", "Poblacion", "Pulung Maragul", "Santo Domingo", "Santo Rosario"],
    "Mabalacat City": ["Atlu-Bola", "Camachiles", "Dau", "Dolores", "Mabiga", "Poblacion", "San Francisco", "Santa Ines"]
  },
  "Bulacan": {
    "Malolos City": ["Bulihan", "Catmon", "Cofradia", "Dakila", "Guinhawa", "Liang", "Lugam", "Mojon", "Panasahan", "Poblacion", "San Gabriel", "San Vicente", "Santo Rosario", "Sumapang Matanda"],
    "Meycauayan City": ["Bancal", "Calvario", "Camalig", "Langka", "Malhacan", "Perez", "Poblacion", "Saluysoy", "Zamora"],
    "City of San Jose del Monte": ["Bagong Buhay", "Citrus", "Graceville", "Kaypian", "Muzon", "Poblacion", "Sapang Palay", "Tungkong Mangga"]
  },
  "Cebu": {
    "Cebu City": ["Apas", "Banilad", "Guadalupe", "Lahug", "Mabolo", "Pardo", "Poblacion", "Punta Princesa", "Talamban", "Tejero", "Tisa"],
    "Mandaue City": ["Bakilid", "Banilad", "Centro", "Guizo", "Ibabao-Estancia", "Maguikay", "Subangdaku", "Tipolo"],
    "Lapu-Lapu City": ["Agus", "Basak", "Gun-ob", "Mactan", "Marigondon", "Pajac", "Poblacion", "Punta Engaño", "Subabasbas"]
  },
  "Davao del Sur": {
    "Davao City": ["Agdao", "Buhangin", "Bucana", "Bunawan", "Calinan", "Matina Crossing", "Matina Aplaya", "Poblacion", "Sasa", "Talomo", "Toril"],
    "Digos City": ["Aplaya", "Colorado", "Cogon", "Dulangan", "Kapatagan", "Matti", "Poblacion", "San Jose", "Zone 1", "Zone 2", "Zone 3"]
  },
  "Ilocos Norte": {
    "Laoag City": ["Barangay 1 (San Lorenzo)", "Barangay 2 (Santa Joaquina)", "Barangay 3 (Nuestra Señora del Rosario)", "Barangay 23 (San Matias)", "Barangay 40 (Balatong)", "Nalbo", "Pila", "Sarrat Road"],
    "Batac City": ["Aglipay", "Baay", "Balanacan", "Ben-agan", "Callaguip", "Palpalicong", "Poblacion", "Quiling Sur", "Rayuray", "Tabug"]
  },
  "Ilocos Sur": {
    "Vigan City": ["Ayusan Norte", "Ayusan Sur", "Beddeng Laud", "Bantay Border", "Capangpangan", "Pantay Fatima", "Poblacion", "Tamag"],
    "Candon City": ["Bagani Campo", "Bagani Gabor", "Calaoa-an", "Caterman", "Darapidap", "Langlangca", "Parioc", "Poblacion Center", "San Juan"]
  },
  "La Union": {
    "San Fernando City": ["Biday", "Cabaroan", "Catbangen", "Dalumpinas Este", "Lingsat", "Madayegdeg", "Poblacion", "San Agustin", "Sevilla", "Tanqui"]
  },
  "Bataan": {
    "Balanga City": ["Bagumbayan", "Cabog-Cabog", "Camacho", "Cupang North", "Cupang Proper", "Doña Francisca", "Ibayo", "Malabia", "Poblacion", "Puerto Rivas", "San Jose", "Tenejero", "Tortugas"]
  },
  "Nueva Ecija": {
    "Cabanatuan City": ["Barrera", "Bitas", "Camp Tinio", "D.S. Garcia", "Kapitan Pepe", "Mabini Homesite", "Poblacion", "San Josef Norte", "San Josef Sur", "Sangitan", "Valdefuente"],
    "Gapan City": ["Bungo", "Mahipon", "Pambuan", "Poblacion", "San Lorenzo", "San Nicolas", "San Vicente", "Santa Cruz", "Santo Cristo"]
  },
  "Tarlac": {
    "Tarlac City": ["Aguso", "Alvindia", "Binauganan", "Cut-cut 1st", "Fairlane", "Matatalaib", "Poblacion", "San Nicolas", "San Rafael", "San Roque", "San Sebastian", "Sepung Calzada", "Tibag"]
  },
  "Zambales": {
    "Olongapo City": ["Asinan", "Barretto", "East Bajac-Bajac", "East Tapinac", "Gordon Heights", "Kalaklan", "Mabayuan", "New Cabalan", "Old Cabalan", "Pag-asa", "Santa Rita", "West Bajac-Bajac", "West Tapinac"],
    "Iba": ["Amungan", "Bangantalingga", "Dirita-Baloguen", "Lipay", "Palanginan", "Poblacion", "San Agustin", "Santa Barbara", "Santo Rosario", "Zone 1", "Zone 2", "Zone 3", "Zone 4", "Zone 5", "Zone 6"]
  },
  "Batangas": {
    "Batangas City": ["Alangilan", "Balagtas", "Bolbok", "Calicanto", "Kumintang Ibaba", "Kumintang Ilaya", "Pallocan Kanluran", "Pallocan Silangan", "Poblacion", "Sampaga", "San Isidro", "Santa Clara"],
    "Lipa City": ["Balintawak", "Bugtong na Pulo", "Dagatan", "Inosluban", "Lodlod", "Marawoy", "Mataas na Lupa", "Poblacion", "Sabang", "Sampaguita", "Tambobong", "Tambo"]
  },
  "Cavite": {
    "Bacoor City": ["Habay 1", "Habay 2", "Mambog 1", "Mambog 2", "Molino 1", "Molino 2", "Molino 3", "Molino 4", "Niog 1", "Niog 2", "Panapaan", "Queens Row Central", "Queens Row East", "Queens Row West", "Salinas 1", "Talaba 1", "Talaba 2", "Zapote 1", "Zapote 2"],
    "Dasmariñas City": ["Burol 1", "Burol 2", "Fatima 1", "Langkaan 1", "Langkaan 2", "Paliparan 1", "Paliparan 2", "Paliparan 3", "Salawag", "Salitran 1", "Salitran 2", "Sampaloc 1", "San Agustin 1", "Zone 1 (Poblacion)"],
    "Imus City": ["Alapan 1-A", "Alapan 1-B", "Anabu 1-A", "Anabu 1-B", "Anabu 2-A", "Bayan Luma", "Bucandala 1", "Malagasang 1-A", "Malagasang 2-A", "Poblacion 1-A", "Tanzang Luma"]
  },
  "Laguna": {
    "Calamba City": ["Bañadero", "Barandal", "Bucal", "Canlubang", "Halang", "Laguerta", "Lecheria", "Makiling", "Mayapa", "Parian", "Poblacion 1", "Real", "Turbina"],
    "Santa Rosa City": ["Aplaya", "Balibago", "Caingin", "Dila", "Don Jose", "Ibaba", "Labas", "Macabling", "Malusak (Poblacion)", "Market Area", "Pooc", "Pulong Santa Cruz", "Sinalhan", "Tagapo"]
  },
  "Rizal": {
    "Antipolo City": ["Bagong Nayon", "Beverly Hills", "Cupang", "Dalig", "Dela Paz (Poblacion)", "Inarawan", "Mambugan", "Mayamot", "Muntindilaw", "San Isidro (Poblacion)", "San Jose (Poblacion)", "San Juan", "San Luis", "San Roque (Poblacion)", "Santa Cruz"]
  },
  "Other Provinces (Nationwide)": {
    "Provincial Capital / City": ["Poblacion", "Barangay 1", "Barangay 2", "Centro", "San Jose", "Santa Cruz", "Santo Niño", "San Isidro"],
    "Municipality Center": ["Poblacion 1", "Poblacion 2", "Central", "Barangay North", "Barangay South", "San Antonio", "San Pedro", "San Roque"]
  }
};

const PHAddress = {
  getProvinces() {
    return Object.keys(PH_ADDRESS_DATA);
  },

  getMunicipalities(province) {
    if (!province || !PH_ADDRESS_DATA[province]) return [];
    return Object.keys(PH_ADDRESS_DATA[province]);
  },

  getBarangays(province, municipality) {
    if (!province || !municipality || !PH_ADDRESS_DATA[province] || !PH_ADDRESS_DATA[province][municipality]) {
      return ["Poblacion", "Centro", "San Jose", "Barangay 1", "Barangay 2"];
    }
    return PH_ADDRESS_DATA[province][municipality];
  },

  formatFullAddress(purok, barangay, municipality, province) {
    const parts = [];
    if (purok && purok.trim()) parts.push(purok.trim());
    if (barangay && barangay.trim()) parts.push(barangay.trim());
    if (municipality && municipality.trim()) parts.push(municipality.trim());
    if (province && province.trim()) parts.push(province.trim());
    return parts.join(', ');
  }
};

if (typeof window !== 'undefined') {
  window.PH_ADDRESS_DATA = PH_ADDRESS_DATA;
  window.PHAddress = PHAddress;
}
