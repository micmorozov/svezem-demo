// Категория 100 - Аренда бульдозера

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "бульдозер";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_100);