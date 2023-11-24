#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 18 - Вывоз мебели и хлама

/*
Вывезти 2|два старых стола
Вывезти стула
Вывезти 2 дивана
Вывезти на свалку два старых дивана
вывезти на ближайшую свалку два старых старых бабушкиных стола
*/

Transp -> AnyWord<kwtype="утилизировать">;
What -> AnyWord<kwtype="мебель">;

Disposal -> Transp (AnyWord+) What;

Garbage -> AnyWord<kwtype="мусор">;

Fact -> Disposal | Garbage;

S -> Fact interp(Category.Category_18);
