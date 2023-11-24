#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 46 - Офисный переезд
What -> "офисный";

Fact -> What;

S -> Fact interp(Category.Category_46);