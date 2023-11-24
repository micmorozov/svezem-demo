// Категория 106 - Расчистка участка

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
Service1 -> "расчистка" "участка";
Service2 -> Transp "мульчир";

Fact -> Service1 | Service2;

S -> Fact interp(Category.Category_106);
