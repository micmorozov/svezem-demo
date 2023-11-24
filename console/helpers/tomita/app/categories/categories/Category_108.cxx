// Категория 108 - Аренда катка

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "каток";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_108);