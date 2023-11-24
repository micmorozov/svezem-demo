#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 12 - Перевозка опасных грузов

Transp -> AnyWord<kwtype="перевезти">;
What -> "опасный";

DangerList -> AnyWord<kwtype="опасный">;

Fact -> Transp (AnyWord+) What | DangerList;

S -> Fact interp(Category.Category_12);
