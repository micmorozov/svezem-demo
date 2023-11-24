#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 48 - Перевозка пианино

Transp -> AnyWord<kwtype="перевезти">;
What -> "пианино";

//Fact -> Transp (AnyWord+) What;
Fact -> What;

S -> Fact interp(Category.Category_48);
