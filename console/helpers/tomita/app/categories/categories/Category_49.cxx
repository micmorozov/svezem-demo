#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 49 - Перевозка дивана

Transp -> AnyWord<kwtype="перевезти">;
What -> "диван";

//Fact -> Transp (AnyWord+) What;
Fact -> What;

S -> Fact interp(Category.Category_49);
